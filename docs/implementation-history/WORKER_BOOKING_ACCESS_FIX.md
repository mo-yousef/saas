# Worker Booking Access Fix

## Issue
Workers were getting "You do not have permission to view this booking" error when trying to access their assigned bookings.

## Root Cause
The issue was in the `dashboard/page-my-assigned-bookings.php` file where the booking was being fetched using the wrong user ID. 

### The Problem:
```php
// WRONG: Using worker ID to fetch booking
$booking_to_view = $bookings_manager->get_booking($single_booking_id, $current_staff_id);
```

### Why This Failed:
1. Bookings are stored in the database with `user_id` set to the **business owner's ID**
2. When a worker tries to fetch a booking using their own ID, the query fails because:
   ```sql
   SELECT * FROM bookings WHERE booking_id = X AND user_id = worker_id
   ```
   This returns no results because `user_id` is actually the business owner's ID, not the worker's ID.

## The Fix
Changed the booking retrieval to use the business owner's ID:

```php
// CORRECT: Using business owner ID to fetch booking
$booking_to_view = $bookings_manager->get_booking($single_booking_id, $business_owner_id);
```

### Why This Works:
1. Bookings are fetched using the correct tenant ID (business owner's ID)
2. The permission check still ensures the booking is assigned to the worker:
   ```php
   if ($booking_to_view && (int)$booking_to_view['assigned_staff_id'] === $current_staff_id)
   ```

## Files Modified

### `dashboard/page-my-assigned-bookings.php`
```php
// Changed from:
$booking_to_view = $bookings_manager->get_booking($single_booking_id, $current_staff_id);

// To:
$booking_to_view = $bookings_manager->get_booking($single_booking_id, $business_owner_id);
```

## Data Flow Explanation

### Database Structure:
```
bookings table:
- booking_id: 123
- user_id: 5 (business owner ID)
- assigned_staff_id: 10 (worker ID)
- customer_name: "John Doe"
- ...
```

### Access Flow:
1. **Worker (ID: 10)** clicks to view booking 123
2. **System gets business owner ID**: 5 (from worker association)
3. **Query executes**: `SELECT * FROM bookings WHERE booking_id = 123 AND user_id = 5`
4. **Booking found**: Returns booking data
5. **Permission check**: `assigned_staff_id (10) === current_staff_id (10)` ✅
6. **Access granted**: Worker can view the booking

### Previous Broken Flow:
1. **Worker (ID: 10)** clicks to view booking 123
2. **Query executes**: `SELECT * FROM bookings WHERE booking_id = 123 AND user_id = 10`
3. **No results**: Because `user_id` is 5 (business owner), not 10 (worker)
4. **Access denied**: "You do not have permission to view this booking"

## Security Maintained
The fix maintains proper security because:
1. Workers can only access bookings from their associated business owner
2. Workers can only view bookings specifically assigned to them (`assigned_staff_id` check)
3. Data isolation between different business owners is preserved
4. Workers cannot access other workers' assignments

## Testing
To test the fix:
1. Log in as a worker
2. Go to "My Assigned Bookings"
3. Click on any assigned booking
4. The booking details should now display correctly
5. Workers should only see bookings assigned to them

## Debug Tools
Created `debug-worker-booking-access.php` to help diagnose similar issues in the future. This script shows:
- Current user information
- Worker-owner associations
- Assigned bookings
- Access test results
- Detailed debugging information

## Additional Debugging Added
Added comprehensive logging to both files:
- `dashboard/page-my-assigned-bookings.php`: Logs booking access attempts
- `dashboard/page-booking-single.php`: Logs permission check details

These logs help identify issues with:
- User role detection
- Business owner associations
- Booking retrieval
- Permission validation

## Conclusion
The fix resolves the worker booking access issue by ensuring bookings are fetched using the correct tenant ID (business owner) while maintaining proper permission checks to ensure workers can only access their assigned bookings.