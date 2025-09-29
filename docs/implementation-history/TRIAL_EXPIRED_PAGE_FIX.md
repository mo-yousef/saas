# Trial Expired Page Fix - Undefined Variable Error

## Issue Description
The trial expired page was showing a PHP warning:
```
Warning: Undefined variable $requested_page in dashboard/dashboard-shell.php on line 35
```

## Root Cause
The `$requested_page` variable was being used in the subscription status check before it was properly defined. The variable definition was happening later in the code, but it was referenced earlier when checking if the trial had expired.

## Fix Applied

### 1. Moved Variable Definition
**Before (Broken):**
```php
// Line 35: Using $requested_page before it's defined
if ($subscription_status === 'expired_trial' && $requested_page !== 'subscription') {
    $requested_page = 'trial-expired';
}

// Line 45: Variable defined later
$requested_page = get_query_var('nordbooking_dashboard_page');
```

**After (Fixed):**
```php
// Line 25: Variable defined FIRST
$requested_page = get_query_var('nordbooking_dashboard_page');

// Line 55: Used after it's properly defined
if ($subscription_status === 'expired_trial' && !in_array($requested_page, ['subscription', 'trial-expired'])) {
    $requested_page = 'trial-expired';
}
```

### 2. Enhanced Logic
- Added proper fallback handling for the trial-expired page
- Improved condition to prevent infinite redirects
- Added special case handling for trial-expired page detection

### 3. Code Structure Improvement
```php
// 1. Define requested page first
$requested_page = get_query_var('nordbooking_dashboard_page');

// 2. Apply fallbacks
if (empty($requested_page)) {
    // Try global variable, URL parsing, etc.
}

// 3. Handle special cases
if (empty($requested_page) && isset($_GET['page']) && $_GET['page'] === 'trial-expired') {
    $requested_page = 'trial-expired';
}

// 4. THEN check subscription status
if (class_exists('\NORDBOOKING\Classes\Subscription')) {
    $subscription_status = \NORDBOOKING\Classes\Subscription::get_subscription_status($current_user_id);
    
    // Safe to use $requested_page here
    if ($subscription_status === 'expired_trial' && !in_array($requested_page, ['subscription', 'trial-expired'])) {
        $requested_page = 'trial-expired';
    }
}
```

## Files Modified
- `dashboard/dashboard-shell.php` - Fixed variable definition order and logic

## Testing
After the fix:
1. ✅ No more undefined variable warnings
2. ✅ Trial expired page loads correctly
3. ✅ Proper redirection for expired trial users
4. ✅ No infinite redirect loops
5. ✅ Subscription page remains accessible

## Prevention
To prevent similar issues in the future:
1. **Define variables before using them**
2. **Use proper fallback chains**
3. **Add safety checks for array access**
4. **Test with different subscription statuses**

## Error Handling Improvements
The fix also includes:
- Better condition checking with `in_array()` instead of single comparison
- Prevention of infinite redirects between trial-expired and subscription pages
- Proper handling of edge cases where page detection fails

## Deployment Notes
- This is a critical fix that should be deployed immediately
- No database changes required
- No breaking changes to existing functionality
- Safe to deploy without downtime

---

**Status: RESOLVED ✅**
The undefined variable error has been fixed and the trial expired page now works correctly without any PHP warnings.