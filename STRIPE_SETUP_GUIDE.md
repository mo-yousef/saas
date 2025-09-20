# Stripe Integration Setup Guide

## Overview
This guide will help you set up Stripe integration for the NORDBOOKING subscription system.

## Prerequisites
1. A Stripe account (sign up at https://stripe.com)
2. Composer installed on your server (for Stripe PHP library)
3. WordPress admin access

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
3. **Endpoint URL**: `https://yourdomain.com/stripe-webhook/`
4. **Events to send**:
   - `checkout.session.completed`
   - `invoice.payment_succeeded`
   - `invoice.payment_failed`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
5. Click **Add endpoint**
6. **Copy the Webhook Secret** (starts with `whsec_`)

### Repeat for both Test and Live modes

## Step 5: Configure WordPress Settings

### Access Stripe Settings:
1. Go to WordPress Admin → **NORDBOOKING** → **Stripe Settings**
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

### Test Mode Testing:
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

## Step 7: Go Live

### When ready for production:
1. Complete Stripe account verification
2. Switch **Test Mode** to ❌ Disabled in WordPress settings
3. Ensure all live keys are properly configured
4. Test with a small real transaction
5. Monitor webhook delivery in Stripe Dashboard

## Troubleshooting

### Common Issues:

#### "Stripe is not properly configured"
- Check that all required keys are filled in
- Verify Price ID is correct
- Ensure you're using the right mode (test/live) keys

#### Webhooks not working:
- Verify webhook URL is accessible: `https://yourdomain.com/stripe-webhook/`
- Check webhook secret matches your settings
- Review webhook logs in Stripe Dashboard

#### Subscription not updating:
- Check webhook delivery in Stripe Dashboard
- Review WordPress error logs
- Verify webhook events are properly configured

### Debug Mode:
Enable WordPress debug logging by adding to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check logs at `/wp-content/debug.log` for Stripe-related errors.

## Security Best Practices

1. **Never expose secret keys** in frontend code
2. **Use HTTPS** for all Stripe interactions
3. **Validate webhook signatures** (automatically handled)
4. **Keep Stripe library updated**
5. **Monitor failed payments** and suspicious activity

## Support

### Resources:
- [Stripe Documentation](https://stripe.com/docs)
- [Stripe PHP Library](https://github.com/stripe/stripe-php)
- [Webhook Testing](https://stripe.com/docs/webhooks/test)

### Need Help?
If you encounter issues:
1. Check Stripe Dashboard logs
2. Review WordPress error logs
3. Test with Stripe's webhook testing tool
4. Contact support with specific error messages

## Features Included

✅ **Subscription Management**
- Create subscriptions
- Cancel subscriptions (at period end)
- Customer portal access
- Trial period support

✅ **Webhook Handling**
- Payment success/failure
- Subscription updates
- Automatic status synchronization

✅ **Security**
- Webhook signature verification
- Secure API key storage
- Test/Live mode separation

✅ **User Experience**
- Seamless checkout flow
- Real-time status updates
- Professional billing portal