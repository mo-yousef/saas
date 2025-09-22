# NORDBOOKING Admin Guide

## Overview
The NORDBOOKING admin functionality has been consolidated into a single, comprehensive admin page that combines all administrative features into one easy-to-use interface. This guide covers all aspects of system administration.

## Accessing the Admin Interface

### Location
- Go to WordPress Admin Dashboard
- Look for **NORDBOOKING Admin** in the main menu (with calendar icon)
- Click to access the consolidated admin interface

## Dashboard Features

### 🎯 Single Admin Page
- All admin functionality accessible from one location
- No more scattered admin pages across different WordPress menus
- Clean, tabbed interface for easy navigation
- Mobile-responsive design
- Improved accessibility

## Tab Overview

### 📊 Dashboard Tab
**Key Performance Indicators (KPIs)**
- Total Business Owners
- Active Subscriptions  
- Users on Trial
- Monthly Recurring Revenue (MRR)
- Total Invoices
- Total Revenue

**Recent Activity**
- Recent Business Owners: Quick overview of newest registrations
- System health status
- Performance metrics

**Quick Actions**
- **Login as User**: Switch to any business owner account for support
- **System Health Check**: Real-time system status
- **Performance Overview**: Memory usage and cache statistics

### 👥 User Management Tab

#### User Hierarchy Display
- **Visual Tree**: Shows business owners and their workers
- **Expandable Lists**: Click arrows (▶) to expand worker lists
- **User Information**: Name, email, role, and status for each user

#### User Actions
- **Edit User Profiles**: Direct access to WordPress user editor
- **Delete Users**: Safe deletion with confirmation dialogs
- **Current User Protection**: Cannot delete your own account
- **Toggle Worker Lists**: Expand/collapse with smooth animations

#### Create New Worker
- **Built-in Form**: Create worker staff accounts directly
- **Automatic Assignment**: Workers automatically assigned to business owners
- **Form Validation**: Real-time validation and error handling
- **Role Selection**: Choose from Manager, Staff, or Viewer roles

#### Worker Role Definitions
- **Manager**: Full operational access (bookings, services, discounts, areas)
- **Staff**: Day-to-day operations (manage bookings, view services)
- **Viewer**: Read-only access to business data

### ⚡ Performance Tab

#### System Health Monitoring
- **Real-time Health**: Current system status and performance
- **Memory Usage**: Current and peak memory consumption
- **Cache Statistics**: Hit rates and cache performance
- **Database Status**: Connection health and query performance

#### Performance Tools
- **Cache Management**: View statistics and clear cache
- **Database Optimization**: One-click database optimization
- **Slow Query Log**: Monitor and identify performance bottlenecks
- **Resource Monitoring**: CPU, memory, and disk usage

#### Optimization Features
- **Automatic Optimization**: Background optimization tasks
- **Performance Alerts**: Notifications for performance issues
- **Resource Limits**: Monitor and alert on resource usage
- **Query Profiling**: Detailed query performance analysis

### 🔧 Debug Tab

#### System Diagnostics
- **File System Check**: Verify performance monitoring files
- **Class Loading Check**: Ensure all performance classes are loaded
- **System Information**: PHP version, memory limits, WordPress info
- **Extension Check**: Verify required PHP extensions

#### Testing Tools
- **Cache Testing**: Test WordPress and custom cache functionality
- **Database Testing**: Verify database connectivity and performance
- **API Testing**: Test external API connections
- **Webhook Testing**: Verify webhook functionality

#### Debug Information
- **Error Logs**: Recent error log entries
- **Performance Metrics**: Detailed performance statistics
- **Configuration Status**: System configuration validation
- **Health Checks**: Comprehensive system health validation

### 💳 Stripe Settings Tab

#### Complete Stripe Configuration
- **Test/Live Mode Toggle**: Easy switching between environments
- **API Key Management**: Secure storage of publishable and secret keys
- **Webhook Configuration**: Webhook endpoint secret management
- **Price ID Configuration**: Subscription pricing setup

#### Configuration Sections
- **General Settings**: Currency, trial days, mode selection
- **Test Mode Settings**: Test API keys and webhook secrets
- **Live Mode Settings**: Production API keys and configuration
- **Validation**: Real-time configuration validation

#### Security Features
- **Secure Storage**: API keys stored securely
- **Configuration Validation**: Real-time validation of settings
- **Test Mode Protection**: Prevents accidental live transactions
- **Webhook Security**: Signature verification setup

### 📈 Subscription Management Tab

#### Subscription Overview
- **Real-time Statistics**: Live subscription metrics
- **Status Distribution**: Visual breakdown of subscription statuses
- **Revenue Tracking**: MRR and revenue analytics
- **Conversion Metrics**: Trial to paid conversion rates

#### Subscription List
- **Comprehensive View**: All subscriptions with detailed information
- **Status Filtering**: Filter by subscription status
- **Search Functionality**: Find specific users or subscriptions
- **Bulk Actions**: Perform actions on multiple subscriptions

#### Individual Actions
**For Active Subscriptions:**
- Cancel Subscription
- Force Expire (emergency use)
- View Invoices
- Access Customer Portal

**For Trial Users:**
- Extend Trial (add 7 days)
- Force Expire
- Convert to Paid

**For All Subscriptions:**
- View User Profile
- Edit Subscription Details
- Sync with Stripe

#### Bulk Operations
- **Extend All Trials**: Add 7 days to all active trials
- **Send Renewal Reminders**: Email reminders for expiring subscriptions
- **Cleanup Expired**: Remove old expired subscription records
- **Sync All**: Synchronize all subscriptions with Stripe

## Key Improvements

### ✅ Enhanced User Management
- **Safe User Deletion**: Confirmation dialogs prevent accidental deletions
- **Hierarchical View**: Clear visual representation of user relationships
- **Role Management**: Easy role assignment and modification
- **Worker Creation**: Streamlined worker invitation process

### ✅ Integrated Performance Monitoring
- **All-in-One**: All performance tools in one place
- **Real-time Monitoring**: Live system monitoring without page switches
- **AJAX Updates**: Smooth user experience with background updates
- **Automated Alerts**: Proactive performance issue detection

### ✅ Streamlined Interface
- **Tabbed Navigation**: Logical grouping of functionality
- **Consistent Styling**: Uniform design across all sections
- **Mobile Responsive**: Works on all device sizes
- **Accessibility**: Improved keyboard navigation and screen reader support

### ✅ Advanced Analytics
- **Real-time Data**: Live statistics and metrics
- **Historical Trends**: Track performance over time
- **Conversion Tracking**: Monitor trial to paid conversions
- **Revenue Analytics**: Detailed revenue reporting

## Usage Guide

### Daily Administration Tasks

#### Monitoring System Health
1. Go to **Performance** tab
2. Check system health indicators
3. Review performance metrics
4. Address any alerts or warnings

#### Managing Users
1. Go to **User Management** tab
2. Review new user registrations
3. Manage worker assignments
4. Handle user support requests

#### Subscription Management
1. Go to **Subscription Management** tab
2. Review subscription statistics
3. Handle subscription issues
4. Process bulk operations as needed

### Weekly Tasks

#### Performance Review
1. Check **Performance** tab for trends
2. Review slow query log
3. Optimize database if needed
4. Clear caches if performance is degraded

#### User Activity Review
1. Review new business owner registrations
2. Check trial conversion rates
3. Identify users needing support
4. Process any pending user requests

#### Revenue Analysis
1. Review MRR trends
2. Analyze conversion rates
3. Identify churn patterns
4. Plan retention strategies

### Monthly Tasks

#### System Maintenance
1. Run database optimization
2. Clean up expired data
3. Review and archive logs
4. Update system documentation

#### Performance Analysis
1. Review monthly performance trends
2. Identify optimization opportunities
3. Plan capacity upgrades if needed
4. Update monitoring thresholds

## Security Features

### 🔐 Access Control
- **Admin-Only Access**: Only WordPress administrators can access
- **Capability Checks**: Proper permission verification
- **Session Management**: Secure session handling
- **Audit Logging**: Track all administrative actions

### 🛡️ Data Protection
- **Nonce Verification**: CSRF protection on all forms
- **Input Sanitization**: All user input properly sanitized
- **SQL Injection Prevention**: Prepared statements for all queries
- **Error Handling**: Secure error messages without sensitive data

### 🔒 Stripe Security
- **API Key Protection**: Secure storage and handling
- **Webhook Verification**: Signature validation for all webhooks
- **Test Mode Safety**: Prevents accidental live transactions
- **PCI Compliance**: Maintained through Stripe integration

## Troubleshooting

### Common Issues

#### Admin Page Not Loading
**Symptoms**: Blank page or errors when accessing admin
**Solutions**:
1. Check WordPress error logs
2. Verify user has admin permissions
3. Clear browser cache
4. Check for plugin conflicts

#### Performance Issues
**Symptoms**: Slow loading or timeouts
**Solutions**:
1. Check **Performance** tab for bottlenecks
2. Clear caches
3. Optimize database
4. Review slow query log

#### User Management Issues
**Symptoms**: Cannot create or manage users
**Solutions**:
1. Verify admin permissions
2. Check WordPress user capabilities
3. Review error logs
4. Test with different browser

#### Subscription Sync Issues
**Symptoms**: Subscription data not updating
**Solutions**:
1. Check Stripe configuration
2. Verify webhook setup
3. Test API connectivity
4. Review webhook logs

### Debug Tools

#### System Information
- PHP version and configuration
- WordPress version and settings
- Database status and performance
- Server resource usage

#### Performance Monitoring
- Real-time performance metrics
- Query performance analysis
- Memory usage tracking
- Cache performance statistics

#### Error Logging
- Recent error log entries
- Performance warnings
- Security alerts
- System notifications

## Best Practices

### Daily Operations
- Monitor system health regularly
- Review new user registrations
- Check subscription status updates
- Address performance alerts promptly

### Security Maintenance
- Review user permissions regularly
- Monitor for suspicious activity
- Keep system updated
- Backup data regularly

### Performance Optimization
- Monitor resource usage
- Optimize database regularly
- Clear caches when needed
- Review slow queries

### User Support
- Use "Login as User" for support
- Monitor trial conversion rates
- Proactively extend trials when appropriate
- Provide timely support responses

## Technical Details

### Files Modified
- `classes/Admin/ConsolidatedAdminPage.php` - Main admin class
- `functions/initialization.php` - Admin page initialization
- `functions.php` - Integration and setup
- Various dashboard and admin files

### Database Integration
- Optimized queries for admin operations
- Proper indexing for performance
- Transaction support for data integrity
- Caching for frequently accessed data

### AJAX Architecture
- Secure AJAX endpoints
- Real-time data updates
- Error handling and recovery
- Performance optimization

## Migration Notes

### From Old System
- Old admin pages automatically disabled
- All existing settings and data preserved
- No data migration required
- Backward compatibility maintained

### Reverting Changes
If needed, you can revert to the old system by:
1. Comment out ConsolidatedAdminPage initialization
2. Uncomment old admin page registrations
3. Clear any cached data
4. Restart web server if needed

## Benefits Summary

- **Efficiency**: Everything in one place saves time
- **User Experience**: Cleaner, more intuitive interface
- **Maintenance**: Easier to maintain and update
- **Performance**: Better resource utilization
- **Security**: Centralized security controls
- **Scalability**: Easy to add new features
- **Monitoring**: Comprehensive system monitoring
- **Analytics**: Detailed business insights

This consolidated admin system provides a comprehensive, efficient, and secure way to manage all aspects of your NORDBOOKING system from a single, intuitive interface.