# Personal and Business Details Implementation

## Overview

The Settings page now includes separate sections for Personal Details and Business Details, providing a clear distinction between user account information and business-specific information used for billing and customer communication.

## Implementation Details

### Personal Details Section
- **First Name**: User's personal first name (editable)
- **Last Name**: User's personal last name (editable)
- **Primary Email**: The email used during registration (read-only, cannot be changed)

### Business Details Section
- **Business Name**: Public name of the business (appears on invoices)
- **Business Email**: Email for customer communication and business correspondence
- **Business Phone**: Business contact number
- **Business Address**: Primary business location (appears on invoices)

## Data Storage

### Personal Details
- Stored as WordPress user meta fields:
  - `first_name` - User meta
  - `last_name` - User meta
  - Primary email - WordPress user table (`user_email`)

### Business Details
- Stored in the tenant settings system:
  - `biz_name` - Business name
  - `biz_email` - Business email
  - `biz_phone` - Business phone
  - `biz_address` - Business address

## Billing Integration

### New Method: `get_user_billing_info()`
The Settings class now includes a `get_user_billing_info()` method that returns comprehensive billing information:

```php
$billing_info = $settings_manager->get_user_billing_info($user_id);
```

Returns:
```php
[
    // Personal Details
    'first_name' => 'John',
    'last_name' => 'Doe',
    'primary_email' => 'john@example.com',
    'full_name' => 'John Doe',
    
    // Business Details
    'business_name' => 'Acme Cleaning Services',
    'business_email' => 'info@acmecleaning.com',
    'business_phone' => '+1-555-0123',
    'business_address' => '123 Main St, City, State 12345',
    
    // Billing Display Info (smart fallbacks)
    'billing_name' => 'Acme Cleaning Services', // Falls back to full_name if no business name
    'billing_email' => 'info@acmecleaning.com', // Falls back to primary_email if no business email
]
```

## Usage for Billing/Invoices

When generating invoices or billing information:

1. **Company Name**: Use `billing_name` (business name with personal name fallback)
2. **Contact Email**: Use `billing_email` (business email with primary email fallback)
3. **Address**: Use `business_address` if available
4. **Phone**: Use `business_phone` if available

## Form Handling

### Non-AJAX Form Submission
The page handles both personal and business details in the same form submission:
- Personal details are saved to user meta
- Business details are saved to tenant settings

### AJAX Form Submission
The `handle_save_business_settings_ajax()` method now:
1. Extracts personal details from the settings data
2. Saves them to user meta
3. Removes them from business settings data
4. Saves remaining business settings normally

## Security Notes

- Primary email cannot be changed through this interface (security measure)
- All inputs are properly sanitized
- Nonce verification is required for all updates
- User authentication is verified before any operations

## Future Enhancements

Consider adding:
- Email change functionality with verification
- Profile picture upload
- Additional personal/business fields as needed
- Audit trail for changes to billing information