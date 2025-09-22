# NORDBOOKING Consolidated Admin Page

## Overview
The NORDBOOKING admin functionality has been consolidated into a single, comprehensive admin page that combines all administrative features into one easy-to-use interface.

## Features

### 🎯 Single Admin Page
- All admin functionality is now accessible from one location: **NORDBOOKING Admin**
- No more scattered admin pages across different WordPress menus
- Clean, tabbed interface for easy navigation

### 📊 Dashboard Tab
- **KPI Cards**: View key metrics at a glance
  - Total Business Owners
  - Active Subscriptions  
  - Users on Trial
  - Monthly Recurring Revenue (MRR)
- **Recent Business Owners**: Quick overview of newest registrations
- **Login as User**: Switch to any business owner account for support

### 👥 User Management Tab
- **User Hierarchy**: Visual tree showing business owners and their workers
- **User Actions**: 
  - Edit user profiles
  - **Delete users** (NEW FEATURE) - Safe deletion with confirmation
  - Toggle worker lists with expand/collapse
- **Create New Worker**: Built-in form to create worker staff accounts
  - Automatic assignment to business owners
  - Form validation and error handling

### ⚡ Performance Tab
- **System Health**: Real-time health monitoring
- **Performance Statistics**: Memory usage, cache stats, query profiling
- **Cache Management**: View cache statistics and clear cache
- **Database Optimization**: One-click database optimization
- **Slow Query Log**: Monitor and identify performance bottlenecks

### 🔧 Debug Tab
- **File System Check**: Verify performance monitoring files
- **Class Loading Check**: Ensure all performance classes are loaded
- **System Information**: PHP version, memory limits, WordPress info
- **Cache Testing**: Test WordPress and custom cache functionality

### 💳 Stripe Settings Tab
- **Complete Stripe Configuration**: All Stripe settings in one place
- **Test/Live Mode Toggle**: Easy switching between environments
- **API Key Management**: Secure storage of publishable and secret keys
- **Webhook Configuration**: Webhook endpoint secret management

## Key Improvements

### ✅ Enhanced User Management
- **User Deletion**: You can now safely delete users directly from the admin interface
- **Confirmation Dialogs**: Prevents accidental deletions
- **Current User Protection**: Cannot delete your own account
- **Hierarchical View**: Clear visual representation of user relationships

### ✅ Integrated Performance Monitoring
- All performance tools in one place
- Real-time monitoring without switching pages
- AJAX-powered updates for smooth user experience

### ✅ Streamlined Interface
- Tabbed navigation for logical grouping
- Consistent styling across all sections
- Mobile-responsive design
- Improved accessibility

## Technical Details

### Files Modified
- `classes/Admin/ConsolidatedAdminPage.php` - New consolidated admin class
- `functions/initialization.php` - Updated to use new admin page
- `functions.php` - Disabled old Stripe settings page
- `admin-performance-dashboard.php` - Disabled separate menu item
- `debug-performance.php` - Disabled separate menu item

### Backward Compatibility
- All existing functionality is preserved
- User switching functionality maintained
- Performance monitoring APIs unchanged
- Stripe configuration remains compatible

### Security Features
- Proper nonce verification for all actions
- Capability checks for admin functions
- AJAX endpoints secured with permissions
- Safe user deletion with confirmations

## Usage

### Accessing the Admin Page
1. Go to WordPress Admin Dashboard
2. Look for **NORDBOOKING Admin** in the main menu (with calendar icon)
3. Click to access the consolidated admin interface

### Managing Users
1. Go to **User Management** tab
2. Click the arrow (▶) next to business owners to expand worker lists
3. Use **Edit** to modify user profiles
4. Use **Delete** to remove users (with confirmation)
5. Use the **Create New Worker** form to add staff members

### Monitoring Performance
1. Go to **Performance** tab
2. View real-time system health and statistics
3. Use **Refresh** buttons to update data
4. Clear cache or optimize database as needed

### Configuring Stripe
1. Go to **Stripe Settings** tab
2. Toggle test mode as needed
3. Enter your API keys
4. Configure webhook secrets
5. Save settings

## Benefits

- **Efficiency**: Everything in one place saves time
- **User Experience**: Cleaner, more intuitive interface
- **Maintenance**: Easier to maintain and update
- **Performance**: Better resource utilization
- **Security**: Centralized security controls
- **Scalability**: Easy to add new features

## Migration Notes

The old admin pages are automatically disabled when the consolidated admin page is active. No data migration is required - all existing settings and data remain intact.

If you need to revert to the old system, simply comment out the ConsolidatedAdminPage initialization in `functions/initialization.php` and uncomment the old admin page registrations.