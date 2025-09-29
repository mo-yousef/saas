# Subscription Management & Expiration Flow - Testing Guide

## Overview
This document provides a comprehensive testing guide for the complete subscription management and expiration flow implementation.

## Features Implemented ✅

### 1. Cancel Subscription Button
- **Trial Users**: "Cancel Trial" button available
- **Pro Subscribers**: "Cancel Subscription" button available
- **Different Behavior**: 
  - Trial cancellation = immediate expiration
  - Subscription cancellation = access until end of billing period

### 2. Free Trial Expiration
- **Dashboard Access**: Blocked for expired trial users
- **Registration Form**: Locked for expired trial users
- **Redirect Logic**: Expired users redirected to subscription page
- **Message Display**: "Your free trial has expired. Upgrade to the Pro Plan."

### 3. Access Control System
- **Automatic Redirects**: Expired users can only access subscription page
- **Protected Pages**: All dashboard pages except subscription
- **Registration Lock**: Expired users cannot access registration form

### 4. Reactivation After Payment
- **Full Access Unlock**: Immediate access restoration after payment
- **Welcome Email**: Automatic email sent after successful subscription
- **Status Sync**: Real-time subscription status synchronization

## Testing Checklist

### A. Cancel Subscription Button Tests

#### For Trial Users:
1. **Access Subscription Page**
   - Navigate to `/dashboard/subscription/`
   - Verify "Cancel Trial" button is visible
   - Verify "Upgrade to Pro Plan" button is also visible

2. **Cancel Trial Process**
   - Click "Cancel Trial" button
   - Confirm dialog: "Are you sure you want to cancel your free trial? You will lose access immediately..."
   - Verify immediate redirect to subscription page with expired message
   - Verify trial status changes to 'expired_trial'

#### For Pro Subscribers:
1. **Access Subscription Page**
   - Navigate to `/dashboard/subscription/`
   - Verify "Cancel Subscription" button is visible
   - Verify "Manage Billing" button is also visible

2. **Cancel Subscription Process**
   - Click "Cancel Subscription" button
   - Confirm dialog: "Are you sure you want to cancel your subscription? You will continue to have access..."
   - Verify subscription is cancelled but access remains until billing period ends

### B. Free Trial Expiration Tests

#### Dashboard Access Control:
1. **Expired Trial User Dashboard Access**
   - Set user status to 'expired_trial' in database
   - Try to access `/dashboard/`
   - Verify redirect to `/dashboard/subscription/?expired=1`
   - Verify warning message displayed

2. **Protected Page Access**
   - Try to access `/dashboard/bookings/`
   - Try to access `/dashboard/customers/`
   - Try to access `/dashboard/services/`
   - Verify all redirect to subscription page

3. **Allowed Page Access**
   - Access `/dashboard/subscription/` - should work
   - Access `/dashboard/trial-expired/` - should work

#### Registration Form Lock:
1. **Expired Trial User Registration**
   - Set user status to 'expired_trial'
   - Try to access `/register/`
   - Verify redirect to subscription page
   - Verify cannot access registration form

### C. Subscription Page Experience

#### For Expired Trial Users:
1. **Special Expired Section**
   - Verify red "Trial Expired - Upgrade Required" card
   - Verify warning message about locked access
   - Verify pricing display ($29/month)
   - Verify benefits list

2. **Upgrade Button**
   - Verify prominent "Subscribe to Pro Plan" button
   - Verify button functionality (creates Stripe checkout)

#### For Active Trial Users:
1. **Trial Benefits Section**
   - Verify blue "Continue with Pro Plan" card
   - Verify trial messaging
   - Verify pricing and features display

2. **Action Buttons**
   - Verify "Upgrade to Pro Plan" button (animated)
   - Verify "Cancel Trial" button

### D. Reactivation After Payment

#### Payment Success Flow:
1. **Complete Stripe Payment**
   - Use test payment method in Stripe
   - Complete checkout process
   - Verify redirect to subscription page with success message

2. **Access Restoration**
   - Verify immediate dashboard access
   - Verify all protected pages accessible
   - Verify registration form accessible (if needed)

3. **Welcome Email**
   - Check email inbox for welcome message
   - Verify subject: "[Site Name] Congratulations! Your plan subscription is active"
   - Verify email content includes feature list and dashboard link

4. **Status Updates**
   - Verify subscription status shows "Pro Plan"
   - Verify "Manage Billing" and "Cancel Subscription" buttons available
   - Verify no more upgrade prompts

## Database Testing

### Subscription Status Values:
- `trial` - Active trial user
- `expired_trial` - Trial has expired
- `active` - Paid subscriber
- `cancelled` - Cancelled but still has access
- `expired` - Subscription expired

### Test Status Changes:
1. **Trial to Expired Trial**
   ```sql
   UPDATE wp_nordbooking_subscriptions 
   SET status = 'expired_trial', trial_ends_at = NOW() 
   WHERE user_id = [USER_ID];
   ```

2. **Expired Trial to Active**
   ```sql
   UPDATE wp_nordbooking_subscriptions 
   SET status = 'active', stripe_subscription_id = 'sub_test123' 
   WHERE user_id = [USER_ID];
   ```

## Email Testing

### Welcome Email Content:
- **Subject**: "[Site Name] Congratulations! Your plan subscription is active"
- **Content**: Personalized greeting, feature list, dashboard link
- **Trigger**: After successful payment and status sync
- **Prevention**: Only sent once per user (tracked in user meta)

### Test Welcome Email:
1. Manually trigger: `nordbooking_send_subscription_welcome_email($user_id)`
2. Check email delivery
3. Verify user meta `nordbooking_welcome_email_sent` is set

## Error Scenarios

### Test Error Handling:
1. **Network Errors**: Disconnect internet during cancellation
2. **Invalid Nonce**: Modify nonce in browser dev tools
3. **Database Errors**: Temporarily break database connection
4. **Stripe Errors**: Use invalid Stripe keys

### Expected Behaviors:
- Graceful error messages
- Button re-enabling after errors
- No data corruption
- Proper logging of errors

## Performance Testing

### Load Testing:
1. **Multiple Concurrent Cancellations**: Test with multiple users
2. **Rapid Status Changes**: Quick succession of status updates
3. **Email Queue**: Multiple welcome emails at once

## Security Testing

### Access Control Security:
1. **Direct URL Access**: Try accessing protected URLs directly
2. **Session Manipulation**: Modify user session data
3. **CSRF Protection**: Verify nonce validation works
4. **SQL Injection**: Test with malicious input

## Browser Compatibility

### Test Browsers:
- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

### Test Features:
- JavaScript functionality
- CSS animations
- AJAX requests
- Form submissions
- Redirects

## Rollback Procedures

### If Issues Found:
1. **Disable Access Control**: Comment out redirect hooks
2. **Restore Button Logic**: Revert to previous button configuration
3. **Database Rollback**: Restore subscription statuses if needed
4. **Email Disable**: Disable welcome email sending

## Monitoring & Logging

### Check Logs For:
- Access control redirects
- Email sending attempts
- Subscription status changes
- Error messages
- Performance issues

### Log Locations:
- WordPress debug log
- Server error logs
- Email delivery logs
- Stripe webhook logs

---

## Success Criteria ✅

The implementation is successful when:

1. **Trial users can cancel and lose access immediately**
2. **Expired trial users cannot access dashboard or registration**
3. **Only subscription page is accessible for expired users**
4. **Payment success restores full access immediately**
5. **Welcome email is sent after successful subscription**
6. **All redirects work correctly**
7. **No security vulnerabilities**
8. **Good user experience with clear messaging**

## Deployment Checklist

Before going live:
- [ ] All tests pass
- [ ] Email delivery configured
- [ ] Stripe webhooks configured
- [ ] Database backups taken
- [ ] Error monitoring enabled
- [ ] Performance monitoring enabled
- [ ] User documentation updated