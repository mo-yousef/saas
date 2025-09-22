# NORDBOOKING Troubleshooting Guide

## Overview

This comprehensive troubleshooting guide covers common issues, their symptoms, causes, and solutions for the NORDBOOKING system.

## Quick Diagnostic Tools

### System Health Check
1. Go to **NORDBOOKING Admin** → **Performance** tab
2. Check system health indicators
3. Review any warnings or errors
4. Use "Refresh" buttons to update data

### Test Files (Admin Only)
- `/debug/test-subscription-system.php` - Complete subscription testing
- `/debug/test-booking-system.php` - Booking system validation
- `/debug/test-discount-system.php` - Discount system testing
- `/debug/test-invoice-system.php` - Invoice system testing

## Common Issues by Category

## 🔐 Authentication & Access Issues

### Issue: Cannot Access Dashboard
**Symptoms:**
- 404 error when accessing dashboard
- Blank page or permission denied
- Redirected to login page repeatedly

**Causes & Solutions:**
1. **User Role Issues**
   - Check user has "Business Owner" role
   - Verify user is properly activated
   - Solution: Edit user in WordPress admin, assign correct role

2. **Permalink Issues**
   - Dashboard URLs not working after setup
   - Solution: Go to Settings → Permalinks → Save Changes

3. **Plugin Conflicts**
   - Other plugins interfering with routing
   - Solution: Deactivate other plugins temporarily to test

### Issue: Worker Cannot Access Dashboard
**Symptoms:**
- Worker gets permission denied
- Dashboard shows limited or no content
- Worker role not working properly

**Solutions:**
1. Check worker role assignment in User Management
2. Verify worker is associated with correct business owner
3. Re-invite worker if necessary
4. Check WordPress user capabilities

## 💳 Subscription Issues

### Issue: Subscription Status Shows "Trial" After Payment
**Symptoms:**
- User completed Stripe checkout successfully
- Dashboard still shows trial status
- Payment visible in Stripe but not reflected in system

**Solutions:**
1. **Immediate Fix:**
   - Click "Refresh Status" button on subscription page
   - Wait 30 seconds for auto-sync (if enabled)

2. **Check Webhook:**
   - Verify webhook URL: `https://yourdomain.com/enhanced-stripe-webhook.php`
   - Check webhook is receiving events in Stripe Dashboard
   - Verify webhook secret matches settings

3. **Manual Sync:**
   - Go to NORDBOOKING Admin → Subscription Management
   - Find user and click sync button
   - Check Stripe Dashboard for subscription status

### Issue: Subscription Not Created
**Symptoms:**
- Stripe checkout completes but no subscription record
- User shows as unsubscribed after payment
- No subscription visible in admin

**Solutions:**
1. **Check Database:**
   - Verify subscription table exists
   - Run `/debug/test-subscription-system.php`
   - Check WordPress error logs

2. **Webhook Issues:**
   - Verify webhook endpoint is accessible
   - Check webhook signature verification
   - Review webhook logs in Stripe Dashboard

3. **API Configuration:**
   - Verify Stripe API keys are correct
   - Check Price ID is valid (starts with `price_`)
   - Ensure test/live mode matches

### Issue: Real-time Sync Not Working
**Symptoms:**
- Auto-refresh toggle doesn't work
- Manual sync buttons don't respond
- Status never updates automatically

**Solutions:**
1. **JavaScript Issues:**
   - Check browser console for errors
   - Verify AJAX endpoints are accessible
   - Clear browser cache

2. **Server Issues:**
   - Check WordPress error logs
   - Verify AJAX handlers are registered
   - Test API connectivity to Stripe

## 🎫 Discount System Issues

### Issue: Discount Code Field Not Showing
**Symptoms:**
- Discount input field not visible in booking form
- Field appears but immediately disappears
- No discount option available

**Solutions:**
1. **Service Configuration:**
   - Check if selected services allow discounts
   - Go to Services → Edit → Enable "Allow Discount Codes"
   - Ensure at least one selected service has discounts enabled

2. **JavaScript Issues:**
   - Check browser console for errors
   - Verify booking form JavaScript is loading
   - Clear browser cache and reload

### Issue: Discount Code Not Applying
**Symptoms:**
- Valid discount code entered but price doesn't change
- Error message shows for valid code
- Discount validates but doesn't calculate

**Solutions:**
1. **Code Validation:**
   - Check discount code exists and is active
   - Verify expiration date hasn't passed
   - Check usage limits haven't been exceeded

2. **Service Compatibility:**
   - Ensure selected services allow discounts
   - Check service-level discount settings
   - Verify discount applies to selected service types

3. **Calculation Issues:**
   - Check JavaScript console for calculation errors
   - Verify discount value and type are correct
   - Test with different discount amounts

### Issue: Usage Count Not Updating
**Symptoms:**
- Discount used successfully but count doesn't increase
- Dashboard shows incorrect usage statistics
- Usage limits not being enforced

**Solutions:**
1. **Database Issues:**
   - Check discount tables exist and are accessible
   - Verify booking includes discount_id
   - Run database migration if needed

2. **Backend Processing:**
   - Check booking creation includes discount processing
   - Verify increment_discount_usage() is called
   - Review WordPress error logs

## 📧 Booking & Email Issues

### Issue: Booking Form Not Submitting
**Symptoms:**
- Form submission fails with error
- Loading indicator shows but nothing happens
- JavaScript errors in console

**Solutions:**
1. **AJAX Issues:**
   - Check AJAX endpoint accessibility
   - Verify nonce validation
   - Check WordPress error logs

2. **Validation Errors:**
   - Check all required fields are filled
   - Verify email format is valid
   - Ensure date/time selection is valid

3. **Server Issues:**
   - Check PHP error logs
   - Verify database connectivity
   - Check memory limits and execution time

### Issue: Confirmation Emails Not Sending
**Symptoms:**
- Booking created but no email sent
- Emails going to spam folder
- Email template not loading

**Solutions:**
1. **Email Configuration:**
   - Check WordPress email settings
   - Verify SMTP configuration if using custom SMTP
   - Test with different email addresses

2. **Template Issues:**
   - Check email template files exist
   - Verify template syntax is correct
   - Test with simplified email content

## 🎨 Frontend Issues

### Issue: Booking Form Styling Issues
**Symptoms:**
- Form appears broken or unstyled
- Layout issues on mobile devices
- CSS not loading properly

**Solutions:**
1. **CSS Loading:**
   - Check CSS files are loading in browser network tab
   - Verify asset versioning is working
   - Clear browser cache

2. **Theme Conflicts:**
   - Check for CSS conflicts with theme
   - Test with default WordPress theme
   - Review CSS specificity issues

### Issue: JavaScript Errors
**Symptoms:**
- Console shows JavaScript errors
- Interactive features not working
- AJAX requests failing

**Solutions:**
1. **Script Loading:**
   - Check JavaScript files are loading
   - Verify script dependencies are met
   - Check for jQuery conflicts

2. **Browser Compatibility:**
   - Test in different browsers
   - Check for ES6+ compatibility issues
   - Verify polyfills if needed

## 🗄️ Database Issues

### Issue: Database Tables Missing
**Symptoms:**
- System shows database errors
- Features not working properly
- Admin shows table doesn't exist errors

**Solutions:**
1. **Run Installation:**
   - Go to NORDBOOKING Admin → Debug tab
   - Check database status
   - Run table creation if needed

2. **Manual Creation:**
   - Check database permissions
   - Run SQL creation scripts manually
   - Verify table structure matches requirements

### Issue: Performance Issues
**Symptoms:**
- Slow page loading
- Database timeouts
- High server resource usage

**Solutions:**
1. **Database Optimization:**
   - Run database optimization from Performance tab
   - Check for missing indexes
   - Review slow query log

2. **Caching:**
   - Enable object caching if available
   - Clear existing caches
   - Implement query result caching

## 🔧 Configuration Issues

### Issue: Stripe Configuration Errors
**Symptoms:**
- "Stripe not properly configured" messages
- Checkout process fails
- API connection errors

**Solutions:**
1. **API Keys:**
   - Verify all required keys are entered
   - Check test/live mode matches keys
   - Ensure keys have proper permissions

2. **Price ID Issues:**
   - Verify Price ID starts with `price_` not `prod_`
   - Check Price ID exists in Stripe Dashboard
   - Ensure price is set to recurring

3. **Webhook Configuration:**
   - Verify webhook URL is accessible
   - Check webhook secret matches
   - Ensure all required events are selected

### Issue: Performance Monitoring Not Working
**Symptoms:**
- Performance tab shows errors
- Monitoring data not updating
- Cache statistics not available

**Solutions:**
1. **File Permissions:**
   - Check performance monitoring files exist
   - Verify file permissions are correct
   - Check server write permissions

2. **Class Loading:**
   - Verify performance classes are loading
   - Check autoloader configuration
   - Review PHP error logs

## 🚨 Emergency Procedures

### System Down
1. Check WordPress error logs immediately
2. Verify database connectivity
3. Check server resources (memory, disk space)
4. Disable plugins temporarily if needed
5. Contact hosting provider if server issues

### Data Corruption
1. Stop all operations immediately
2. Create database backup
3. Identify scope of corruption
4. Restore from recent backup if necessary
5. Run data integrity checks

### Security Breach
1. Change all passwords immediately
2. Update all API keys
3. Check access logs for suspicious activity
4. Update WordPress and all plugins
5. Run security scan

## 🔍 Debug Information Collection

### For Support Requests
When contacting support, include:

1. **System Information:**
   - WordPress version
   - PHP version
   - Theme version
   - Active plugins list

2. **Error Details:**
   - Exact error messages
   - Steps to reproduce
   - Browser and device information
   - Screenshots if applicable

3. **Log Files:**
   - WordPress debug log entries
   - PHP error log entries
   - Browser console errors
   - Server error logs

4. **Test Results:**
   - Results from relevant test files
   - System health check results
   - Configuration validation results

### Debug Mode Setup
Add to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

## 📞 Getting Help

### Self-Service Resources
1. Check this troubleshooting guide first
2. Run appropriate test files
3. Check system health dashboard
4. Review WordPress and PHP error logs

### Escalation Process
1. Gather debug information
2. Run comprehensive tests
3. Document exact steps to reproduce
4. Include system configuration details
5. Contact support with complete information

### Prevention
1. Regular system health monitoring
2. Keep WordPress and plugins updated
3. Regular database backups
4. Monitor performance metrics
5. Test changes in staging environment first

This troubleshooting guide covers the most common issues encountered with the NORDBOOKING system. For issues not covered here, use the debug tools and contact support with detailed information.