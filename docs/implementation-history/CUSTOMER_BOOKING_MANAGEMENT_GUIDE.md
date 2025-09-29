# Customer Booking Management System

## Overview

The Customer Booking Management System allows customers to easily reschedule or cancel their bookings through a secure, unique link. This system enhances customer experience while automatically keeping business owners informed of any changes.

## Features

### For Customers
- **Reschedule Bookings**: Select a new date and time from available slots
- **Cancel Bookings**: Cancel with optional reason for feedback
- **View Booking Details**: See complete booking information including services, pricing, and business details
- **Mobile-Friendly**: Responsive design works on all devices
- **Secure Access**: Token-based authentication ensures only the customer can manage their booking

### For Business Owners
- **Automatic Updates**: All changes are immediately reflected in the dashboard
- **Email Notifications**: Receive instant notifications when customers make changes
- **Change Tracking**: Complete audit trail of all booking modifications
- **Business Branding**: Customer-facing pages display your business name and logo

## How It Works

### 1. Booking Creation
When a customer creates a booking, the system:
- Generates a unique, secure token for the booking
- Creates a customer management link using the token
- Includes the link in the booking confirmation email

### 2. Customer Access
Customers can access their booking management page by:
- Clicking the link in their confirmation email
- The link is valid only for that specific booking and customer

### 3. Making Changes
On the management page, customers can:
- View their complete booking details
- Reschedule to a new date and time
- Cancel the booking with an optional reason
- See real-time updates to their booking status

### 4. Business Notification
When customers make changes:
- The business owner receives an email notification
- The dashboard is automatically updated
- All changes are logged for record-keeping

## Technical Implementation

### Files Created/Modified

1. **page-customer-booking-management.php**
   - Main customer-facing booking management page
   - Handles display of booking details and change forms
   - Includes responsive design and user-friendly interface

2. **classes/Bookings.php** (Modified)
   - Added AJAX handlers for reschedule and cancel operations
   - Added token verification methods
   - Added notification methods for business owners
   - Added link generation method

3. **classes/Notifications.php** (Modified)
   - Updated booking confirmation emails to include management links
   - Added customer booking management section to emails

4. **classes/Routes/BookingFormRouter.php** (Modified)
   - Added routing for customer booking management page
   - Added URL rewrite rules

5. **functions.php** (Modified)
   - Added AJAX handler registration

### Security Features

- **Token-Based Authentication**: Each booking gets a unique token generated using the booking ID, customer email, and WordPress salt
- **Timing-Safe Comparison**: Uses `hash_equals()` to prevent timing attacks
- **CSRF Protection**: All form submissions use WordPress nonces
- **Input Validation**: All user inputs are sanitized and validated
- **Status Restrictions**: Only pending and confirmed bookings can be modified

### Database Changes

No database schema changes are required. The system uses existing booking tables and adds functionality through:
- Token generation based on existing booking data
- Status updates using existing status field
- Audit logging through WordPress error logs

## Usage Instructions

### For Developers

1. **Enable the System**: The system is automatically enabled when the files are in place
2. **Test the System**: Use `test-customer-booking-management.php` to see recent bookings and their management links
3. **Customize Styling**: Modify the CSS in `page-customer-booking-management.php` to match your theme
4. **Configure Notifications**: Email templates can be customized in the Notifications class

### For Business Owners

1. **Automatic Integration**: The system works automatically with existing bookings
2. **Email Templates**: Booking confirmation emails now include customer management links
3. **Dashboard Updates**: All customer changes appear immediately in your dashboard
4. **Notifications**: You'll receive email notifications for all customer changes

### For Customers

1. **Access**: Click the "Manage Your Booking" link in your confirmation email
2. **Reschedule**: Select a new date and time, add an optional reason
3. **Cancel**: Confirm cancellation with an optional reason
4. **Updates**: See changes reflected immediately on the page

## URL Structure

- **Management Page**: `/customer-booking-management/?token=SECURE_TOKEN`
- **Token Format**: SHA256 hash of booking_id + customer_email + wp_salt()

## Error Handling

The system includes comprehensive error handling:
- Invalid or expired tokens show appropriate error messages
- Form validation prevents invalid date/time selections
- Database errors are logged and user-friendly messages are shown
- Network errors are handled gracefully with retry options

## Customization Options

### Styling
- Modify CSS in `page-customer-booking-management.php`
- Add custom business branding elements
- Adjust responsive breakpoints for mobile devices

### Time Slots
- Modify the time slot generation in the reschedule form
- Add business-specific availability rules
- Integrate with existing availability systems

### Notifications
- Customize email templates in the Notifications class
- Add additional notification methods (SMS, webhooks, etc.)
- Modify notification timing and content

### Business Logic
- Add custom validation rules for rescheduling
- Implement cancellation policies and restrictions
- Add integration with payment systems for refunds

## Testing

Use the provided test file to verify functionality:

```php
// Access the test page
/test-customer-booking-management.php

// This will show:
// - Recent bookings with management links
// - System overview and features
// - Technical implementation details
```

## Support and Maintenance

### Monitoring
- Check WordPress error logs for any issues
- Monitor email delivery for notifications
- Track customer usage through dashboard analytics

### Updates
- The system is designed to work with existing booking workflows
- Future updates will maintain backward compatibility
- Database migrations are not required for basic functionality

### Troubleshooting
- Verify rewrite rules are flushed after installation
- Check email configuration for notification delivery
- Ensure proper file permissions for template files

## Integration with Existing Systems

The Customer Booking Management System integrates seamlessly with:
- Existing booking creation workflows
- Dashboard booking management
- Email notification systems
- User authentication and security
- Mobile and responsive design themes

No changes to existing booking processes are required - the system adds functionality without disrupting current operations.