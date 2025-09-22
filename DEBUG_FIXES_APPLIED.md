# Debug Fixes Applied for AJAX Registration Error

## Issue Description
The AJAX registration was returning a JSON parsing error with the message:
```
SyntaxError: Unexpected token '<', " <div id="e"... is not valid JSON
```

This indicates that HTML was being returned instead of JSON, typically caused by PHP errors or unexpected output.

## Fixes Applied

### 1. Output Buffer Cleaning
Added `ob_clean()` at the beginning of AJAX handlers to prevent any previous output from interfering with JSON responses:

```php
// Clean any output that might have been generated
if (ob_get_level()) {
    ob_clean();
}
```

### 2. Enhanced Error Handling
Added comprehensive try-catch blocks around all AJAX methods with proper error logging and JSON error responses.

### 3. Database Access Safety
Added checks for class existence before using Database class methods:

```php
if (!$user_id && class_exists('NORDBOOKING\Classes\Database')) {
    // Database operations
}
```

### 4. Settings Manager Error Handling
Wrapped settings operations in try-catch blocks to prevent registration failure if settings operations fail:

```php
try {
    $settings_manager->update_setting($user->ID, 'biz_name', $company_name);
} catch (Exception $e) {
    error_log("NORDBOOKING: Error saving company name to settings: " . $e->getMessage());
    // Continue with registration even if settings save fails
}
```

### 5. Slug Generation Safety
Added error handling around slug generation to prevent registration failure if slug creation fails:

```php
try {
    // Slug generation logic
} catch (Exception $e) {
    error_log('NORDBOOKING: Error during slug generation: ' . $e->getMessage());
    // Continue with registration even if slug generation fails
}
```

### 6. Debug Information
Enhanced error responses to include debug information (file, line, trace) for easier troubleshooting:

```php
wp_send_json_error(['message' => $e->getMessage(), 'debug' => [
    'file' => $e->getFile(),
    'line' => $e->getLine(),
    'trace' => $e->getTraceAsString()
]]);
```

## Debug Tools Created

### 1. `debug-company-validation.php`
Tests the company name validation method in isolation:
```
http://yoursite.com/debug-company-validation.php?company_name=Test%20Company
```

### 2. `debug-registration.php`
Tests class loading and basic system functionality:
```
http://yoursite.com/debug-registration.php
```

### 3. `test-ajax-endpoint.html`
Frontend test page for testing AJAX endpoints directly:
```
http://yoursite.com/test-ajax-endpoint.html
```

## Expected Results

After these fixes:
1. AJAX registration should return proper JSON responses
2. Company name validation should work correctly
3. Registration should complete successfully even if some non-critical operations fail
4. Better error messages should help identify any remaining issues

## Testing Steps

1. **Test Company Name Validation:**
   ```
   http://yoursite.com/debug-company-validation.php?company_name=Test%20Company
   ```

2. **Test System Status:**
   ```
   http://yoursite.com/debug-registration.php
   ```

3. **Test Registration Form:**
   - Go to registration page
   - Fill out form with valid data
   - Check browser console for any errors
   - Verify JSON responses are returned

## Monitoring

Check error logs for any remaining issues:
- Look for "NORDBOOKING:" prefixed log entries
- Monitor for any PHP fatal errors or warnings
- Verify that registration completes successfully

## Rollback Plan

If issues persist, the changes can be reverted by:
1. Removing the `ob_clean()` calls
2. Simplifying error handling back to basic try-catch
3. Removing debug information from error responses

The core functionality remains unchanged, only error handling and output management have been enhanced.