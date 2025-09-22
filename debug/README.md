# NORDBOOKING Debug & Test Files

This directory contains all debug and test files for the NORDBOOKING system. These files are used for system testing, debugging, and maintenance.

## File Organization

### System Testing
- `test-booking-system.php` - Comprehensive booking system testing
- `test-subscription-system.php` - Complete subscription system testing
- `test-discount-system.php` - Discount system functionality testing
- `test-invoice-system.php` - Invoice system testing
- `test-settings-js.php` - JavaScript settings testing

### Debug Tools
- `debug-performance.php` - Performance monitoring and debugging
- `debug-settings.php` - System settings debugging
- `debug-subscriptions.php` - Subscription system debugging
- `debug-subscriptions-admin.php` - Admin subscription debugging
- `debug-user-subscription.php` - User-specific subscription debugging
- `debug-discount-system.php` - Discount system step-by-step debugging
- `debug-service-options.php` - Service options debugging

### Flow Testing
- `test-discount-flow.php` - End-to-end discount flow testing

### Performance Tools
- `admin-performance-dashboard.php` - Admin performance monitoring dashboard
- `performance_monitoring.php` - Core performance monitoring system

## Usage Guidelines

### For Development
1. Use test files to validate system functionality
2. Run debug tools to identify issues
3. Monitor performance during development
4. Test complete user flows before deployment

### For Production Troubleshooting
1. Use debug tools to identify issues
2. Run specific system tests to isolate problems
3. Monitor performance metrics
4. Generate debug reports for support

### Security Note
These files should be restricted to admin users only and may contain sensitive system information. Ensure proper access controls are in place.

## File Descriptions

### Test Files
Each test file provides comprehensive testing for its respective system component with detailed reporting and error identification.

### Debug Files
Debug files provide step-by-step analysis of system components with detailed logging and diagnostic information.

### Performance Files
Performance monitoring tools provide real-time system metrics and optimization recommendations.

## Best Practices

1. **Regular Testing**: Run test files regularly during development
2. **Performance Monitoring**: Monitor system performance continuously
3. **Debug Logging**: Enable debug logging when troubleshooting
4. **Security**: Restrict access to debug files in production
5. **Documentation**: Document any issues found and solutions applied