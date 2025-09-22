# NORDBOOKING Installation Guide

## Overview

This guide provides step-by-step instructions for installing and configuring the NORDBOOKING WordPress theme with all its features and dependencies.

## System Requirements

### Server Requirements
- **PHP**: 7.4 or higher (8.0+ recommended)
- **MySQL**: 5.7+ or MariaDB 10.2+
- **WordPress**: 5.0 or higher (latest version recommended)
- **SSL Certificate**: Required for Stripe integration
- **Memory**: 256MB+ PHP memory limit recommended
- **Disk Space**: 100MB+ available space

### PHP Extensions Required
- `curl` - For API communications
- `json` - For data processing
- `mbstring` - For string handling
- `openssl` - For secure communications
- `zip` - For file operations

### Optional but Recommended
- **Redis** or **Memcached** - For object caching
- **CDN** - For static asset delivery
- **Backup Solution** - For data protection

## Pre-Installation Checklist

### 1. WordPress Setup
- [ ] Fresh WordPress installation or existing site
- [ ] Admin access to WordPress dashboard
- [ ] FTP/SFTP access to server files
- [ ] Database access (for advanced configuration)

### 2. Stripe Account
- [ ] Stripe account created (https://stripe.com)
- [ ] Test mode API keys available
- [ ] Live mode API keys (for production)
- [ ] Webhook endpoint capability

### 3. Email Configuration
- [ ] WordPress email sending working
- [ ] SMTP configuration (recommended)
- [ ] Email templates accessible

## Installation Steps

### Step 1: Theme Installation

#### Option A: Upload via WordPress Admin
1. Download the NORDBOOKING theme package
2. Go to **Appearance** → **Themes** → **Add New**
3. Click **Upload Theme**
4. Select the theme ZIP file
5. Click **Install Now**
6. Click **Activate**

#### Option B: FTP Upload
1. Extract the theme package
2. Upload the `nordbooking` folder to `/wp-content/themes/`
3. Go to **Appearance** → **Themes**
4. Find NORDBOOKING theme and click **Activate**

### Step 2: Install Dependencies

#### Composer Installation (Recommended)
```bash
cd /path/to/wp-content/themes/nordbooking
composer install
```

#### Manual Installation
If Composer is not available:
1. Download Stripe PHP library from GitHub
2. Extract to `vendor/stripe/stripe-php/` in theme directory
3. Verify autoloader is working

### Step 3: Database Setup

#### Automatic Setup
The theme will automatically create required database tables on first activation.

#### Manual Setup (if needed)
If automatic setup fails, run the installation script:
```php
// Access via browser (admin only)
https://yourdomain.com/wp-content/themes/nordbooking/install-optimizations.php
```

#### Verify Database Tables
Required tables should be created:
- `wp_nordbooking_services`
- `wp_nordbooking_bookings`
- `wp_nordbooking_booking_items`
- `wp_nordbooking_customers`
- `wp_nordbooking_subscriptions`
- `wp_nordbooking_discounts`
- `wp_nordbooking_worker_invitations`
- `wp_nordbooking_worker_associations`

### Step 4: Initial Configuration

#### Access Admin Panel
1. Go to WordPress Admin Dashboard
2. Look for **NORDBOOKING Admin** in the main menu
3. Click to access the consolidated admin interface

#### Basic Settings
1. Go to **Dashboard** tab
2. Review system health status
3. Check for any configuration warnings
4. Address any issues identified

### Step 5: Stripe Configuration

#### Get Stripe Keys
1. Log into your Stripe Dashboard
2. Go to **Developers** → **API keys**
3. Copy your publishable and secret keys (test mode first)

#### Configure in WordPress
1. Go to **NORDBOOKING Admin** → **Stripe Settings**
2. Enable **Test Mode**
3. Enter your test API keys:
   - **Test Publishable Key**: `pk_test_...`
   - **Test Secret Key**: `sk_test_...`
4. Click **Save Changes**

#### Create Subscription Product
1. In Stripe Dashboard, go to **Products**
2. Click **Add Product**
3. Create your subscription product:
   - **Name**: "NORDBOOKING Pro Subscription"
   - **Price**: Set your monthly price
   - **Billing**: Monthly recurring
4. Copy the **Price ID** (starts with `price_`)
5. Add Price ID to WordPress Stripe settings

#### Setup Webhooks
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
5. Copy the **Webhook Secret**
6. Add webhook secret to WordPress Stripe settings

### Step 6: Test Installation

#### Run System Tests
1. Access test page: `/debug/test-subscription-system.php` (admin only)
2. Run comprehensive system test
3. Address any issues identified
4. Verify all components are working

#### Test Subscription Flow
1. Create a test user account
2. Go to subscription page
3. Click "Subscribe Now"
4. Use test card: `4242 4242 4242 4242`
5. Complete checkout
6. Verify subscription status updates

#### Test Booking System
1. Create a test service
2. Access public booking form
3. Complete a test booking
4. Verify booking appears in dashboard
5. Test email notifications

### Step 7: Performance Optimization

#### Enable Caching
1. Go to **NORDBOOKING Admin** → **Performance**
2. Check cache status
3. Enable object caching if available
4. Configure cache settings

#### Database Optimization
1. Run database optimization from Performance tab
2. Verify indexes are created
3. Check query performance
4. Monitor slow queries

#### Asset Optimization
1. Verify CSS/JS files are loading
2. Check asset versioning is working
3. Configure CDN if available
4. Test page load speeds

## Post-Installation Configuration

### User Roles Setup

#### Business Owner Accounts
1. Create business owner user accounts
2. Assign "Business Owner" role
3. Configure business slugs for custom URLs
4. Test dashboard access

#### Worker Management
1. Test worker invitation system
2. Create test worker accounts
3. Verify role-based access control
4. Test permission boundaries

### Service Configuration

#### Create Services
1. Go to **Dashboard** → **Services**
2. Create your first service
3. Configure service options
4. Upload service images and icons
5. Test service booking flow

#### Service Areas
1. Configure service areas if needed
2. Set geographic boundaries
3. Test area-based service availability

### Discount System

#### Create Discount Codes
1. Go to **Dashboard** → **Discounts**
2. Create test discount codes
3. Configure usage limits and expiration
4. Test discount application in booking form

### Email Configuration

#### Test Email Delivery
1. Complete a test booking
2. Verify confirmation emails are sent
3. Check email formatting and content
4. Test different email scenarios

#### SMTP Configuration (Recommended)
1. Install SMTP plugin if needed
2. Configure SMTP settings
3. Test email delivery reliability
4. Monitor email logs

## Security Configuration

### SSL Certificate
1. Ensure SSL certificate is installed
2. Force HTTPS for all pages
3. Test Stripe integration with HTTPS
4. Verify webhook endpoints are secure

### User Security
1. Configure strong password policies
2. Enable two-factor authentication if available
3. Regular security updates
4. Monitor user access logs

### API Security
1. Secure API key storage
2. Regular key rotation
3. Monitor API usage
4. Implement rate limiting

## Backup Configuration

### Database Backups
1. Configure automated database backups
2. Test backup restoration
3. Store backups securely
4. Regular backup verification

### File Backups
1. Backup theme files and uploads
2. Include configuration files
3. Test file restoration
4. Offsite backup storage

## Monitoring Setup

### Performance Monitoring
1. Configure performance monitoring
2. Set up alerting for issues
3. Monitor resource usage
4. Track key metrics

### Error Monitoring
1. Enable WordPress debug logging
2. Monitor PHP error logs
3. Set up error alerting
4. Regular log review

### Business Monitoring
1. Monitor subscription metrics
2. Track booking conversion rates
3. Monitor revenue trends
4. Set up business alerts

## Troubleshooting Installation Issues

### Common Installation Problems

#### Theme Not Activating
**Symptoms**: Theme activation fails or shows errors
**Solutions**:
1. Check PHP version compatibility
2. Verify required PHP extensions
3. Check file permissions
4. Review WordPress error logs

#### Database Tables Not Created
**Symptoms**: Features not working, database errors
**Solutions**:
1. Check database permissions
2. Run manual installation script
3. Verify MySQL version compatibility
4. Check for plugin conflicts

#### Stripe Integration Issues
**Symptoms**: Subscription features not working
**Solutions**:
1. Verify API keys are correct
2. Check SSL certificate
3. Test webhook connectivity
4. Review Stripe dashboard logs

#### Performance Issues
**Symptoms**: Slow loading, timeouts
**Solutions**:
1. Check server resources
2. Enable caching
3. Optimize database
4. Review slow query log

### Getting Help

#### Self-Service Resources
1. Check system health dashboard
2. Run diagnostic tests
3. Review error logs
4. Consult troubleshooting guide

#### Support Information
When contacting support, include:
- WordPress version
- PHP version
- Theme version
- Error messages
- System test results
- Server configuration details

## Production Deployment

### Pre-Production Checklist
- [ ] All tests passing
- [ ] SSL certificate installed
- [ ] Stripe live mode configured
- [ ] Email delivery tested
- [ ] Backups configured
- [ ] Monitoring setup
- [ ] Performance optimized

### Go-Live Process
1. Switch Stripe to live mode
2. Update webhook URLs
3. Test live transactions
4. Monitor system health
5. Verify all functionality
6. Update documentation

### Post-Launch Monitoring
1. Monitor system performance
2. Check error logs regularly
3. Track business metrics
4. User feedback collection
5. Regular security updates

## Maintenance Schedule

### Daily Tasks
- Monitor system health
- Check error logs
- Review subscription activity
- Address user issues

### Weekly Tasks
- Performance review
- Database optimization
- Security updates
- Backup verification

### Monthly Tasks
- System updates
- Security audit
- Performance analysis
- Business metrics review

## Conclusion

Following this installation guide will ensure a proper NORDBOOKING setup with all features working correctly. Regular maintenance and monitoring will keep the system running smoothly and securely.

For additional help, refer to the other documentation files in the `/docs/` directory or contact support with detailed system information.