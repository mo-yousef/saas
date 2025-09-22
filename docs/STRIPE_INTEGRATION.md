# Stripe Integration Guide

## Overview
Complete guide for setting up Stripe integration with the NORDBOOKING subscription system, including payment processing, webhooks, and subscription management.

## Prerequisites
1. A Stripe account (sign up at https://stripe.com)
2. Composer installed on your server (for Stripe PHP library)
3. WordPress admin access
4. SSL certificate (required for production)

## Step 1: Install Stripe PHP Library

### Option A: Using Composer (Recommended)
```bash
cd /path/to/your/theme
composer install
```

### Option B: Manual Installation
1. Download Stripe PHP library from https://github.com/stripe/stripe-php
2. Extract to `vendor/stripe/stripe-php/` in your theme directory

## Step 2: Create Stripe Products and Prices

### In Stripe Dashboard:
1. Go to **Products** → **Add Product**
2. Create a product (e.g., "NORDBOOKING Pro Subscription")
3. Add a recurring price:
   - **Price**: Set your subscription price (e.g., $29.00)
   - **Billing period**: Monthly or Yearly
   - **Currency**: USD (or your preferred currency)
4. **Copy the Price ID** (starts with `price_`) - you'll need this later

### Finding Your Price ID
If you have an existing product but need the Price ID:

1. **Your Product Information**
   - Product ID format: `prod_xxxxxxxxxxxxx`
   - Need: Price ID (starts with `price_`)

2. **Locate the Price**
   - Go to Stripe Dashboard → Products
   - Find your product and click on it
   - Look for prices listed with format: `price_xxxxxxxxxxxxx`
   - Copy the **Price ID** (NOT the Product ID)

**Common Mistakes:**
- ❌ Wrong: Using Product ID (`prod_T2e9kSp0dB2q5s`)
- ✅ Correct: Using Price ID (`price_1234567890abcdef`)

## Step 3: Get Stripe API Keys

### Test Mode Keys (for development):
1. In Stripe Dashboard, ensure you're in **Test mode** (toggle in left sidebar)
2. Go to **Developers** → **API keys**
3. Copy:
   - **Publishable key** (starts with `pk_test_`)
   - **Secret key** (starts with `sk_test_`)

### Live Mode Keys (for production):
1. Switch to **Live mode** in Stripe Dashboard
2. Go to **Developers** → **API keys**
3. Copy:
   - **Publishable key** (starts with `pk_live_`)
   - **Secret key** (starts with `sk_live_`)

## Step 4: Set Up Webhooks

### Create Webhook Endpoint:
1. In Stripe Dashboard, go to **Developers** → **Webhooks**
2. Click **Add endpoint**
3. **Endpoint URL**: `https://yourdomain.com/enhanced-stripe-webhook.php`
4. **Events to send**:
   - `checkout.session.completed`
   - `invoice.payment_succeeded`
   - `invoice.payment_failed`
   - `customer.subscription.created`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
   - `customer.subscription.trial_will_end`
5. Click **Add endpoint**
6. **Copy the Webhook Secret** (starts with `whsec_`)

### Enhanced Webhook Features
The system uses `enhanced-stripe-webhook.php` which provides:
- Comprehensive logging system
- Better error handling
- Support for all Stripe events
- Automatic email notifications
- Real-time status updates
- Security enhancements

### Repeat for both Test and Live modes

## Step 5: Configure WordPress Settings

### Access Stripe Settings:
1. Go to WordPress Admin → **NORDBOOKING Admin** → **Stripe Settings** tab
2. Fill in the configuration:

#### General Settings:
- **Test Mode**: ✅ Enable for development, ❌ Disable for production
- **Currency**: Select your currency (USD, EUR, etc.)
- **Trial Days**: Number of free trial days (default: 7)

#### Test Mode Settings:
- **Test Publishable Key**: Your `pk_test_` key
- **Test Secret Key**: Your `sk_test_` key
- **Test Webhook Secret**: Your test `whsec_` key

#### Live Mode Settings:
- **Live Publishable Key**: Your `pk_live_` key
- **Live Secret Key**: Your `sk_live_` key
- **Live Webhook Secret**: Your live `whsec_` key
- **Price ID**: Your Stripe price ID (e.g., `price_1234567890`)

3. Click **Save Changes**

## Step 6: Test the Integration

### Comprehensive Testing
Use the built-in test page: `/test-subscription-system.php` (admin only)

This provides:
- Full system testing
- Quick health checks
- Stripe connection testing
- User-specific testing
- Detailed test reports
- Visual test results

### Manual Testing Steps:
1. Ensure **Test Mode** is enabled in WordPress settings
2. Create a test user account
3. Go to **Dashboard** → **Subscription**
4. Click **Subscribe Now**
5. Use Stripe test card: `4242 4242 4242 4242`
6. Complete the checkout process
7. Verify subscription status updates correctly

### Test Cards:
- **Success**: `4242 4242 4242 4242`
- **Declined**: `4000 0000 0000 0002`
- **Requires Authentication**: `4000 0025 0000 3155`
- **Insufficient Funds**: `4000 0000 0000 9995`

## Step 7: Real-time Synchronization

### Enhanced Subscription Management
The system includes real-time sync features:

- **Automatic Sync**: Status updates every 30 seconds when enabled
- **Manual Sync**: Quick sync and deep sync options
- **Cache Management**: Intelligent caching with 5-minute TTL
- **Background Sync**: Scheduled sync checks for all subscriptions

### User Experience Features:
- **No Page Refreshes**: All updates happen via AJAX
- **Visual Feedback**: Loading indicators and status animations
- **Countdown Timers**: Real-time countdown to trial/billing dates
- **Status Indicators**: Clear visual status with health indicators

## Step 8: Go Live

### Pre-Production Checklist:
- [ ] Complete Stripe account verification
- [ ] SSL certificate installed and working
- [ ] All live API keys configured
- [ ] Webhook endpoints accessible
- [ ] Test transactions completed successfully
- [ ] Backup procedures in place

### Going Live:
1. Complete Stripe account verification
2. Switch **Test Mode** to ❌ Disabled in WordPress settings
3. Ensure all live keys are properly configured
4. Test with a small real transaction
5. Monitor webhook delivery in Stripe Dashboard

## Troubleshooting

### Common Issues:

#### "Stripe is not properly configured"
- Check that all required keys are filled in
- Verify Price ID is correct (starts with `price_`, not `prod_`)
- Ensure you're using the right mode (test/live) keys

#### Webhooks not working:
- Verify webhook URL is accessible: `https://yourdomain.com/enhanced-stripe-webhook.php`
- Check webhook secret matches your settings
- Review webhook logs in Stripe Dashboard
- Ensure SSL certificate is valid

#### Subscription not updating:
- Check webhook delivery in Stripe Dashboard
- Review WordPress error logs
- Verify webhook events are properly configured
- Use the "Refresh Status" button on subscription page

#### Status shows "Trial" after payment:
- Click "Refresh Status" button on subscription page
- Check Stripe Dashboard to confirm subscription is active
- Verify webhook URL is accessible
- Check WordPress error logs for webhook errors

### Debug Tools:

#### System Health Check
Access the admin dashboard for real-time monitoring:
- System health status
- Performance statistics
- Cache management
- Database optimization
- Slow query monitoring

#### Testing Suite
Use `/test-subscription-system.php` for comprehensive testing:
- Configuration validation
- API connectivity tests
- Webhook simulation
- User flow testing

### Debug Mode:
Enable WordPress debug logging by adding to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check logs at `/wp-content/debug.log` for Stripe-related errors.

## Security Best Practices

### API Key Management
1. **Never expose secret keys** in frontend code
2. **Use environment variables** for sensitive data
3. **Rotate keys regularly** in production
4. **Monitor API usage** for suspicious activity

### Webhook Security
1. **Validate webhook signatures** (automatically handled)
2. **Use HTTPS only** for webhook endpoints
3. **Implement rate limiting** on webhook endpoints
4. **Log all webhook events** for audit trails

### General Security
1. **Use HTTPS** for all Stripe interactions
2. **Keep Stripe library updated**
3. **Monitor failed payments** and suspicious activity
4. **Implement proper error handling**

## Advanced Features

### Invoice Management
The system includes comprehensive invoice management:
- **Customer Access**: View and download invoices from subscription page
- **Admin Tools**: View customer invoices from admin panel
- **PDF Downloads**: Direct links to Stripe-generated PDFs
- **Statistics**: Revenue tracking and invoice analytics

### Analytics & Monitoring
Built-in analytics provide:
- Total subscriptions and revenue
- Conversion rates (trial to active)
- Churn rate analysis
- Monthly Recurring Revenue (MRR)
- System health monitoring

### Customer Portal
Integration with Stripe Customer Portal:
- Self-service billing management
- Payment method updates
- Invoice downloads
- Subscription modifications

## API Reference

### Key Classes
- `\NORDBOOKING\Classes\Subscription` - Core subscription management
- `\NORDBOOKING\Classes\SubscriptionManager` - Enhanced management with sync
- `\NORDBOOKING\Classes\SubscriptionTester` - Comprehensive testing suite
- `\NORDBOOKING\Classes\InvoiceManager` - Invoice management

### AJAX Endpoints
- `nordbooking_real_time_sync` - Force real-time sync
- `nordbooking_subscription_status_check` - Quick status check
- `nordbooking_run_subscription_test` - Run system tests
- `nordbooking_sync_all_subscriptions` - Sync all subscriptions
- `nordbooking_get_invoices` - Get customer invoices

## Support Resources

### Documentation
- [Stripe Documentation](https://stripe.com/docs)
- [Stripe PHP Library](https://github.com/stripe/stripe-php)
- [Webhook Testing](https://stripe.com/docs/webhooks/test)

### Internal Tools
- System health dashboard
- Comprehensive test suite
- Real-time monitoring
- Debug information panels

### Getting Help
If you encounter issues:
1. Check this documentation first
2. Use the built-in testing tools
3. Review system health dashboard
4. Check WordPress and PHP error logs
5. Test with Stripe's webhook testing tool
6. Contact support with specific error messages and test results

## Features Included

✅ **Subscription Management**
- Create and manage subscriptions
- Cancel subscriptions (at period end)
- Customer portal access
- Trial period support
- Real-time status synchronization

✅ **Webhook Handling**
- Payment success/failure processing
- Subscription lifecycle events
- Automatic status synchronization
- Comprehensive error handling

✅ **Security**
- Webhook signature verification
- Secure API key storage
- Test/Live mode separation
- Rate limiting and CSRF protection

✅ **User Experience**
- Seamless checkout flow
- Real-time status updates
- Professional billing portal
- Mobile-responsive design

✅ **Admin Tools**
- Comprehensive dashboard
- Real-time analytics
- System health monitoring
- Advanced testing suite

This integration provides a complete, production-ready subscription system with enterprise-level features and monitoring capabilities.