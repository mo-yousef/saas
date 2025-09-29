# Invoice List Feature Documentation

## Overview

The Invoice List feature provides comprehensive invoice management for NORDBOOKING subscriptions, allowing both customers and administrators to view, download, and manage Stripe invoices.

## Features Implemented

### Frontend (Customer-facing)

1. **Subscription Page Invoice List**
   - Location: `/dashboard/subscription` page
   - Displays all paid invoices for the logged-in customer
   - Shows invoice number, amount, date, and billing period
   - Provides direct download links for PDF invoices
   - Includes "View Online" links to Stripe-hosted invoice pages
   - Auto-refreshes invoice data
   - Only visible for active/trial subscribers

2. **Invoice Actions**
   - **Download PDF**: Direct link to Stripe-generated PDF
   - **View Online**: Opens Stripe-hosted invoice page in new tab
   - **Refresh**: Manual refresh of invoice list

### Backend (Admin-facing)

1. **Admin Dashboard Integration**
   - Added invoice statistics to main dashboard KPIs
   - Shows total invoices and total revenue
   - Integrated with existing subscription management

2. **Subscription Management**
   - "View Invoices" button for each customer in subscription table
   - Modal popup showing customer's invoice history
   - Same download and view functionality as frontend

3. **Invoice Statistics**
   - Total number of invoices
   - Total revenue across all customers
   - Monthly revenue tracking
   - Currency-aware formatting

## Technical Implementation

### Classes Created

1. **InvoiceManager** (`classes/InvoiceManager.php`)
   - Singleton pattern for consistent access
   - Handles Stripe API integration
   - Provides AJAX endpoints
   - Manages invoice data formatting
   - Includes security checks for invoice ownership

### AJAX Endpoints

1. **`nordbooking_get_invoices`**
   - Gets invoices for current logged-in user
   - Security: Nonce verification + user authentication

2. **`nordbooking_get_invoice_pdf`**
   - Gets PDF URL for specific invoice
   - Security: Ownership verification + nonce

3. **`nordbooking_admin_get_customer_invoices`**
   - Admin-only endpoint for viewing customer invoices
   - Security: Admin capability check + nonce

### Database Integration

- No additional database tables required
- Uses existing subscription data to link users to Stripe customers
- Fetches invoice data directly from Stripe API

### Security Features

1. **User Authentication**
   - Only logged-in users can access their invoices
   - Admin-only access for customer invoice viewing

2. **Invoice Ownership Verification**
   - Verifies user owns the requested invoice before providing access
   - Prevents unauthorized access to other customers' invoices

3. **Nonce Protection**
   - All AJAX requests protected with WordPress nonces
   - Prevents CSRF attacks

## File Modifications

### Modified Files

1. **`dashboard/page-subscription.php`**
   - Added invoice list section
   - Added CSS styles for invoice table
   - Added JavaScript for invoice loading and display

2. **`classes/Admin/ConsolidatedAdminPage.php`**
   - Added "View Invoices" button to subscription table
   - Added invoice modal functionality
   - Added invoice statistics to dashboard KPIs
   - Added admin AJAX handler

3. **`functions/initialization.php`**
   - Added InvoiceManager initialization

### New Files

1. **`classes/InvoiceManager.php`** - Main invoice management class
2. **`test-invoice-system.php`** - Test page for verifying functionality
3. **`INVOICE_SYSTEM_DOCUMENTATION.md`** - This documentation

## Usage Instructions

### For Customers

1. Navigate to the subscription page (`/dashboard/subscription`)
2. If you have an active subscription, you'll see an "Invoice History" section
3. Click "PDF" to download invoice as PDF
4. Click "View" to open invoice in Stripe's hosted page
5. Use "Refresh" button to update the invoice list

### For Administrators

1. Go to NORDBOOKING Admin → Subscription Management
2. Find the customer in the subscription table
3. Click "View Invoices" to see their invoice history
4. Use the same download/view functionality as customers
5. View invoice statistics on the main dashboard

## Configuration Requirements

### Stripe Setup

1. **API Keys**: Test/Live publishable and secret keys must be configured
2. **Webhooks**: Webhook endpoint must be set up for invoice events
3. **Products**: At least one subscription product/price must exist

### WordPress Setup

1. **User Roles**: Customers must have appropriate subscription status
2. **Permissions**: Admin users need `manage_options` capability
3. **AJAX**: WordPress AJAX functionality must be working

## Testing

### Test Page

Access `/test-invoice-system.php` (admin only) to verify:
- Class loading
- Stripe configuration
- AJAX endpoint registration
- Page integration

### Manual Testing

1. **Create Test Subscription**
   - Use Stripe test mode
   - Create subscription for test user
   - Verify invoice appears in list

2. **Test Download Functionality**
   - Click PDF download link
   - Verify PDF opens/downloads correctly

3. **Test Admin Functionality**
   - View customer invoices from admin panel
   - Verify modal displays correctly

## Troubleshooting

### Common Issues

1. **No Invoices Showing**
   - Check Stripe configuration
   - Verify user has paid invoices
   - Check browser console for JavaScript errors

2. **PDF Download Not Working**
   - Verify Stripe API keys are correct
   - Check if invoice exists in Stripe
   - Verify user owns the invoice

3. **Admin Modal Not Loading**
   - Check admin permissions
   - Verify AJAX endpoints are registered
   - Check browser console for errors

### Debug Steps

1. Check WordPress error logs
2. Verify Stripe webhook is receiving events
3. Test AJAX endpoints directly
4. Use browser developer tools to inspect network requests

## Future Enhancements

### Potential Improvements

1. **Invoice Filtering**
   - Filter by date range
   - Filter by amount
   - Search by invoice number

2. **Bulk Operations**
   - Download multiple invoices as ZIP
   - Email invoice summaries

3. **Enhanced Statistics**
   - Revenue charts
   - Payment method breakdown
   - Failed payment tracking

4. **Notifications**
   - Email notifications for new invoices
   - Payment reminder system

## API Reference

### InvoiceManager Methods

```php
// Get customer invoices
$invoices = $invoice_manager->get_customer_invoices($user_id, $limit);

// Get invoice statistics
$stats = $invoice_manager->get_invoice_statistics();

// Get PDF URL for specific invoice
$pdf_url = $invoice_manager->get_invoice_pdf_url($invoice_id);
```

### AJAX Endpoints

```javascript
// Get current user's invoices
jQuery.post(ajaxurl, {
    action: 'nordbooking_get_invoices',
    nonce: nonce
});

// Get customer invoices (admin only)
jQuery.post(ajaxurl, {
    action: 'nordbooking_admin_get_customer_invoices',
    user_id: userId,
    _ajax_nonce: nonce
});
```

## Support

For issues or questions regarding the invoice system:

1. Check this documentation first
2. Review WordPress and PHP error logs
3. Test with Stripe's test mode
4. Verify all configuration requirements are met

---

*Last updated: December 2024*