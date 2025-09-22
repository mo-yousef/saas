# NORDBOOKING Development Notes

## Overview

This document contains important development guidelines, technical notes, and best practices for maintaining and extending the NORDBOOKING system.

## Development Guidelines

### Asset Versioning & Cache Busting

The theme uses a `NORDBOOKING_VERSION` constant defined in `functions.php` to version its CSS and JavaScript assets. **Always increment this version number** when making changes to any `.js` or `.css` files to ensure users' browsers download the new files instead of using cached versions.

```php
// functions.php
define( 'NORDBOOKING_VERSION', '0.1.24' );
```

### Code Standards

#### PHP Standards
- Follow WordPress coding standards
- Use PSR-4 autoloading for classes
- Implement comprehensive error handling
- Use prepared statements for all database queries
- Sanitize all user input and escape all output

#### JavaScript Standards
- Use modern ES6+ syntax where supported
- Implement proper error handling
- Use jQuery in no-conflict mode (`jQuery` instead of `$`)
- Implement debouncing for AJAX requests
- Provide user feedback for all actions

#### CSS Standards
- Use CSS custom properties for theming
- Implement responsive design principles
- Follow BEM methodology for class naming
- Optimize for performance and accessibility

### Database Design

#### Table Structure
All NORDBOOKING tables use the `wp_nordbooking_` prefix and follow WordPress conventions:

```sql
-- Example table structure
CREATE TABLE wp_nordbooking_example (
    id int(11) NOT NULL AUTO_INCREMENT,
    user_id int(11) NOT NULL,
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_user_id (user_id)
);
```

#### Indexing Strategy
- Primary keys on all tables
- Foreign key indexes for relationships
- Composite indexes for common query patterns
- Avoid over-indexing to maintain write performance

### Security Best Practices

#### Input Validation
```php
// Always sanitize input
$email = sanitize_email($_POST['email']);
$text = sanitize_text_field($_POST['text']);
$textarea = sanitize_textarea_field($_POST['content']);

// Validate data types
$user_id = intval($_POST['user_id']);
$amount = floatval($_POST['amount']);
```

#### Output Escaping
```php
// Escape output based on context
echo esc_html($user_input);
echo esc_attr($attribute_value);
echo esc_url($url);
echo wp_kses_post($html_content);
```

#### Nonce Verification
```php
// Always verify nonces for forms and AJAX
if (!wp_verify_nonce($_POST['nonce'], 'action_name')) {
    wp_send_json_error(['message' => 'Security check failed']);
    return;
}
```

### Performance Optimization

#### Caching Strategy
```php
// Use WordPress object cache
$cache_key = "nordbooking_data_{$user_id}";
$data = wp_cache_get($cache_key);

if (false === $data) {
    $data = expensive_operation();
    wp_cache_set($cache_key, $data, '', 300); // 5 minutes
}
```

#### Database Optimization
```php
// Use efficient queries
$results = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$table} WHERE user_id = %d AND status = %s LIMIT %d",
    $user_id, $status, $limit
));

// Avoid N+1 queries - use JOINs or batch loading
```

#### Asset Optimization
- Minimize HTTP requests
- Use asset concatenation and minification
- Implement lazy loading for images
- Use CDN for static assets

## Architecture Patterns

### Repository Pattern
```php
class BookingRepository {
    public function findByUser($user_id, $limit = 20) {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table} WHERE user_id = %d LIMIT %d",
                $user_id, $limit
            )
        );
    }
}
```

### Service Layer Pattern
```php
class BookingService {
    private $repository;
    private $validator;
    
    public function createBooking(array $data) {
        $validation = $this->validator->validate($data);
        if (!$validation->isValid()) {
            return new WP_Error('validation_failed', $validation->getErrors());
        }
        
        return $this->repository->create($data);
    }
}
```

### Observer Pattern for Hooks
```php
// Use WordPress hooks for extensibility
do_action('nordbooking_booking_created', $booking_id, $booking_data);
$filtered_data = apply_filters('nordbooking_booking_data', $data, $context);
```

## Component Architecture

### Dialog Component

The theme includes a universal dialog component located in `assets/js/dialog.js`. Use this component for consistency across all modals and dialogs.

```javascript
const myDialog = new MoBookingDialog({
    title: "Confirm Action",
    content: "<p>Are you sure you want to proceed?</p>",
    buttons: [
        {
            label: "Cancel",
            class: "secondary",
            onClick: (dialog) => dialog.close(),
        },
        {
            label: "Confirm",
            class: "primary",
            onClick: (dialog) => {
                console.log("Confirmed!");
                dialog.close();
            },
        },
    ],
});
myDialog.show();
```

### AJAX Handler Pattern
```php
// Consistent AJAX handler structure
add_action('wp_ajax_nordbooking_action', 'nordbooking_action_handler');
add_action('wp_ajax_nopriv_nordbooking_action', 'nordbooking_action_handler');

function nordbooking_action_handler() {
    // Security checks
    if (!wp_verify_nonce($_POST['nonce'], 'nordbooking_nonce')) {
        wp_send_json_error(['message' => 'Security check failed']);
        return;
    }
    
    // Permission checks
    if (!current_user_can('required_capability')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
        return;
    }
    
    try {
        // Process request
        $result = process_action($_POST);
        wp_send_json_success($result);
    } catch (Exception $e) {
        error_log('NORDBOOKING Action Error: ' . $e->getMessage());
        wp_send_json_error(['message' => 'Action failed']);
    }
}
```

## Testing Guidelines

### Unit Testing
```php
// Example test structure
class BookingServiceTest extends WP_UnitTestCase {
    public function test_create_booking_with_valid_data() {
        $service = new BookingService();
        $data = ['customer_email' => 'test@example.com'];
        
        $result = $service->createBooking($data);
        
        $this->assertFalse(is_wp_error($result));
        $this->assertIsInt($result);
    }
}
```

### Integration Testing
- Test complete user flows
- Verify database operations
- Test API integrations
- Validate email delivery

### Performance Testing
- Load testing for concurrent users
- Database query performance
- Memory usage monitoring
- Cache effectiveness

## Error Handling

### Logging Strategy
```php
// Consistent error logging
error_log('NORDBOOKING [Component]: Error message with context');

// Use different log levels
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('NORDBOOKING DEBUG: Detailed debug information');
}
```

### User-Friendly Error Messages
```php
// Provide helpful error messages
if (is_wp_error($result)) {
    $error_code = $result->get_error_code();
    
    switch ($error_code) {
        case 'invalid_email':
            $message = 'Please provide a valid email address.';
            break;
        case 'booking_conflict':
            $message = 'This time slot is no longer available.';
            break;
        default:
            $message = 'An unexpected error occurred. Please try again.';
    }
    
    wp_send_json_error(['message' => $message]);
}
```

## Deployment Guidelines

### Environment Configuration
```php
// Use environment-specific configuration
if (defined('WP_ENVIRONMENT_TYPE')) {
    switch (WP_ENVIRONMENT_TYPE) {
        case 'development':
            define('NORDBOOKING_DEBUG', true);
            break;
        case 'staging':
            define('NORDBOOKING_DEBUG', false);
            break;
        case 'production':
            define('NORDBOOKING_DEBUG', false);
            break;
    }
}
```

### Database Migrations
```php
// Version-based migrations
function nordbooking_check_db_version() {
    $current_version = get_option('nordbooking_db_version', '0');
    
    if (version_compare($current_version, NORDBOOKING_DB_VERSION, '<')) {
        nordbooking_run_migrations($current_version);
        update_option('nordbooking_db_version', NORDBOOKING_DB_VERSION);
    }
}
```

### Asset Management
- Use versioned assets for cache busting
- Implement asset concatenation for production
- Use CDN for static assets
- Optimize images and media files

## Monitoring and Maintenance

### Health Checks
```php
// Implement health check endpoints
function nordbooking_health_check() {
    $health = [
        'database' => check_database_connection(),
        'stripe' => check_stripe_connectivity(),
        'cache' => check_cache_functionality(),
        'email' => check_email_configuration()
    ];
    
    return $health;
}
```

### Performance Monitoring
- Monitor database query performance
- Track memory usage patterns
- Monitor API response times
- Track user experience metrics

### Maintenance Tasks
- Regular database optimization
- Cache cleanup and optimization
- Log file rotation and cleanup
- Security updates and patches

## Extension Guidelines

### Adding New Features
1. Follow existing architectural patterns
2. Implement proper security measures
3. Add comprehensive error handling
4. Include unit and integration tests
5. Update documentation

### Plugin Integration
```php
// Make features extensible through hooks
$booking_data = apply_filters('nordbooking_booking_data', $data, $context);
do_action('nordbooking_booking_created', $booking_id, $booking_data);
```

### API Development
```php
// RESTful API endpoints
register_rest_route('nordbooking/v1', '/bookings', [
    'methods' => 'GET',
    'callback' => 'nordbooking_get_bookings',
    'permission_callback' => 'nordbooking_check_permissions'
]);
```

## Common Pitfalls

### WordPress-Specific Issues
- Always use `jQuery` instead of `$` in JavaScript
- Verify nonces for all form submissions
- Use WordPress functions for database operations
- Follow WordPress coding standards

### Performance Issues
- Avoid N+1 database queries
- Implement proper caching strategies
- Optimize database indexes
- Monitor memory usage

### Security Issues
- Never trust user input
- Always escape output
- Use prepared statements for queries
- Implement proper access controls

## Resources

### WordPress Development
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [WordPress Theme Handbook](https://developer.wordpress.org/themes/)

### PHP Development
- [PSR Standards](https://www.php-fig.org/psr/)
- [PHP Best Practices](https://phpbestpractices.org/)

### JavaScript Development
- [MDN Web Docs](https://developer.mozilla.org/)
- [jQuery Documentation](https://api.jquery.com/)

### Testing
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [WordPress Unit Testing](https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/)

This development guide should be updated as the system evolves and new patterns emerge. Always prioritize security, performance, and maintainability in all development decisions.