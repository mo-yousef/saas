# NORDBOOKING Invoice System

## Overview

The NORDBOOKING Invoice System provides comprehensive invoice management for subscriptions, allowing both customers and administrators to view, download, and manage Stripe invoices with full integration into the existing dashboard.

## Features

### 📄 Customer Invoice Access
- **Subscription Page Integration**: Invoice list directly on subscription page
- **PDF Downloads**: Direct links to Stripe-generated PDF invoices
- **Online Viewing**: Links to Stripe-hosted invoice pages
- **Auto-refresh**: Automatic invoice data updates
- **Access Control**: Only visible for active/trial subscribers

### 🔧 Admin Invoice Management
- **Customer Invoice Viewing**: View any customer's invoice history
- **Modal Interface**: Clean popup interface for invoice management
- **Bulk Operations**: Manage multiple invoices efficiently
- **Revenue Tracking**: Total revenue and invoice statistics

### 📊 Analytics & Reporting
- **Revenue Statistics**: Total revenue across all customers
- **Invoice Counts**: Track total number of invoices
- **Monthly Tracking**: Monthly revenue analysis
- **Currency Support**: Multi-currency formatting

## System Architecture

### Core Components

#### InvoiceManager Class (`classes/InvoiceManager.php`)
Singleton pattern class providing:
- Stripe API integration for invoice retrieval
- Security checks for invoice ownership
- AJAX endpoint management
- Data formatting and presentation
- Caching for performance optimization

**Key Methods:**
```php
// Get customer invoices with pagination
public function get_customer_invoices($user_id, $limit = 10);

// Get invoice statistics
public function get_invoice_statistics();

// Get PDF URL for specific invoice
public function get_invoice_pdf_url($invoice_id);

// Verify invoice ownership
private function verify_invoice_ownership($invoice_id, $user_id);
```

### Database Integration
- **No Additional Tables**: Uses existing subscription data
- **Stripe Integration**: Fetches data directly from Stripe API
- **User Linking**: Links users to Stripe customers via subscription records
- **Caching**: Implements intelligent caching for performance

### Security Architecture
- **User Authentication**: Only logged-in users can access their invoices
- **Ownership Verification**: Strict verification of invoice ownership
- **Admin Controls**: Admin-only access for customer invoice viewing
- **CSRF Protection**: All AJAX requests protected with WordPress nonces

## Frontend Implementation

### Customer Interface

#### Subscription Page Integration
Location: `/dashboard/subscription/`

**Invoice History Section:**
- Displays all paid invoices for the logged-in customer
- Shows invoice number, amount, date, and billing period
- Provides download and view actions
- Includes refresh functionality

**Visual Elements:**
```html
<div class="invoice-history-section">
    <h3>Invoice History</h3>
    <div class="invoice-actions">
        <button id="refresh-invoices" class="btn btn-secondary">
            Refresh Invoices
        </button>
    </div>
    <div id="invoice-list">
        <!-- Invoices loaded via AJAX -->
    </div>
</div>
```

#### JavaScript Implementation
```javascript
// Load customer invoices
function loadCustomerInvoices() {
    jQuery('#invoice-list').html('<div class="loading">Loading invoices...</div>');
    
    jQuery.post(ajaxurl, {
        action: 'nordbooking_get_invoices',
        nonce: nordbooking_ajax.nonce
    }, function(response) {
        if (response.success) {
            displayInvoices(response.data);
        } else {
            showInvoiceError(response.data.message);
        }
    });
}

// Display invoices in table format
function displayInvoices(invoices) {
    if (invoices.length === 0) {
        jQuery('#invoice-list').html('<p>No invoices found.</p>');
        return;
    }
    
    let html = '<table class="invoice-table">';
    html += '<thead><tr><th>Invoice</th><th>Amount</th><th>Date</th><th>Period</th><th>Actions</th></tr></thead>';
    html += '<tbody>';
    
    invoices.forEach(function(invoice) {
        html += `<tr>
            <td>${invoice.number}</td>
            <td>${invoice.amount_formatted}</td>
            <td>${invoice.date_formatted}</td>
            <td>${invoice.period}</td>
            <td>
                <a href="${invoice.pdf_url}" target="_blank" class="btn btn-sm btn-primary">PDF</a>
                <a href="${invoice.hosted_url}" target="_blank" class="btn btn-sm btn-secondary">View</a>
            </td>
        </tr>`;
    });
    
    html += '</tbody></table>';
    jQuery('#invoice-list').html(html);
}
```

### Admin Interface

#### Consolidated Admin Integration
Location: **NORDBOOKING Admin** → **Subscription Management**

**Features:**
- "View Invoices" button for each customer
- Modal popup for invoice history
- Same functionality as customer interface
- Additional admin controls

#### Modal Implementation
```javascript
// Show customer invoices in modal
function showCustomerInvoices(userId, userName) {
    const modal = jQuery('#customer-invoices-modal');
    modal.find('.modal-title').text(`Invoices for ${userName}`);
    modal.find('.modal-body').html('<div class="loading">Loading invoices...</div>');
    modal.show();
    
    jQuery.post(ajaxurl, {
        action: 'nordbooking_admin_get_customer_invoices',
        user_id: userId,
        _ajax_nonce: nordbooking_admin.nonce
    }, function(response) {
        if (response.success) {
            displayAdminInvoices(response.data);
        } else {
            modal.find('.modal-body').html(`<p class="error">${response.data.message}</p>`);
        }
    });
}
```

## Backend Implementation

### AJAX Endpoints

#### Customer Invoice Endpoint
```php
add_action('wp_ajax_nordbooking_get_invoices', 'nordbooking_get_invoices_handler');

function nordbooking_get_invoices_handler() {
    // Security checks
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Authentication required']);
        return;
    }
    
    if (!wp_verify_nonce($_POST['nonce'], 'nordbooking_dashboard_nonce')) {
        wp_send_json_error(['message' => 'Security check failed']);
        return;
    }
    
    $user_id = get_current_user_id();
    $invoice_manager = \NORDBOOKING\Classes\InvoiceManager::get_instance();
    
    try {
        $invoices = $invoice_manager->get_customer_invoices($user_id);
        wp_send_json_success($invoices);
    } catch (Exception $e) {
        wp_send_json_error(['message' => 'Failed to load invoices']);
    }
}
```

#### Admin Invoice Endpoint
```php
add_action('wp_ajax_nordbooking_admin_get_customer_invoices', 'nordbooking_admin_get_customer_invoices_handler');

function nordbooking_admin_get_customer_invoices_handler() {
    // Admin capability check
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
        return;
    }
    
    // Nonce verification
    if (!wp_verify_nonce($_POST['_ajax_nonce'], 'nordbooking_admin_nonce')) {
        wp_send_json_error(['message' => 'Security check failed']);
        return;
    }
    
    $user_id = intval($_POST['user_id']);
    $invoice_manager = \NORDBOOKING\Classes\InvoiceManager::get_instance();
    
    try {
        $invoices = $invoice_manager->get_customer_invoices($user_id);
        wp_send_json_success($invoices);
    } catch (Exception $e) {
        wp_send_json_error(['message' => 'Failed to load customer invoices']);
    }
}
```

### Stripe Integration

#### Invoice Retrieval
```php
public function get_customer_invoices($user_id, $limit = 10) {
    // Get user's Stripe customer ID
    $stripe_customer_id = $this->get_user_stripe_customer_id($user_id);
    if (!$stripe_customer_id) {
        return [];
    }
    
    try {
        // Configure Stripe
        $this->configure_stripe();
        
        // Retrieve invoices from Stripe
        $invoices = \Stripe\Invoice::all([
            'customer' => $stripe_customer_id,
            'status' => 'paid',
            'limit' => $limit,
            'expand' => ['data.subscription']
        ]);
        
        // Format invoices for display
        return $this->format_invoices($invoices->data);
        
    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log('Stripe API Error: ' . $e->getMessage());
        throw new Exception('Failed to retrieve invoices from Stripe');
    }
}
```

#### Data Formatting
```php
private function format_invoices($stripe_invoices) {
    $formatted = [];
    
    foreach ($stripe_invoices as $invoice) {
        $formatted[] = [
            'id' => $invoice->id,
            'number' => $invoice->number ?: $invoice->id,
            'amount' => $invoice->amount_paid,
            'amount_formatted' => $this->format_currency($invoice->amount_paid, $invoice->currency),
            'currency' => strtoupper($invoice->currency),
            'date' => $invoice->created,
            'date_formatted' => date('M j, Y', $invoice->created),
            'period' => $this->format_billing_period($invoice),
            'status' => $invoice->status,
            'pdf_url' => $invoice->invoice_pdf,
            'hosted_url' => $invoice->hosted_invoice_url,
            'subscription_id' => $invoice->subscription
        ];
    }
    
    return $formatted;
}
```

## Analytics & Statistics

### Revenue Tracking
```php
public function get_invoice_statistics() {
    $stats = [
        'total_invoices' => 0,
        'total_revenue' => 0,
        'currency' => 'USD',
        'monthly_revenue' => []
    ];
    
    try {
        $this->configure_stripe();
        
        // Get all paid invoices
        $invoices = \Stripe\Invoice::all([
            'status' => 'paid',
            'limit' => 100 // Adjust based on needs
        ]);
        
        $monthly_totals = [];
        
        foreach ($invoices->data as $invoice) {
            $stats['total_invoices']++;
            $stats['total_revenue'] += $invoice->amount_paid;
            
            // Group by month
            $month = date('Y-m', $invoice->created);
            if (!isset($monthly_totals[$month])) {
                $monthly_totals[$month] = 0;
            }
            $monthly_totals[$month] += $invoice->amount_paid;
        }
        
        $stats['monthly_revenue'] = $monthly_totals;
        $stats['total_revenue_formatted'] = $this->format_currency($stats['total_revenue'], 'usd');
        
    } catch (Exception $e) {
        error_log('Invoice statistics error: ' . $e->getMessage());
    }
    
    return $stats;
}
```

### Dashboard Integration
```php
// Add invoice statistics to admin dashboard
public function add_invoice_kpis($kpis) {
    $invoice_stats = $this->get_invoice_statistics();
    
    $kpis['total_invoices'] = [
        'label' => 'Total Invoices',
        'value' => number_format($invoice_stats['total_invoices']),
        'icon' => 'receipt'
    ];
    
    $kpis['total_revenue'] = [
        'label' => 'Total Revenue',
        'value' => $invoice_stats['total_revenue_formatted'],
        'icon' => 'dollar-sign'
    ];
    
    return $kpis;
}
```

## Configuration & Setup

### Stripe Requirements
1. **API Keys**: Valid Stripe API keys (test/live)
2. **Webhooks**: Webhook endpoint configured for invoice events
3. **Subscriptions**: At least one subscription product/price
4. **Invoices**: Paid invoices must exist in Stripe

### WordPress Requirements
1. **User Roles**: Customers must have appropriate subscription status
2. **Permissions**: Admin users need `manage_options` capability
3. **AJAX**: WordPress AJAX functionality must be working
4. **SSL**: HTTPS required for Stripe integration

### File Integration
Modified files:
- `dashboard/page-subscription.php` - Added invoice list section
- `classes/Admin/ConsolidatedAdminPage.php` - Added admin invoice features
- `functions/initialization.php` - Added InvoiceManager initialization

New files:
- `classes/InvoiceManager.php` - Core invoice management
- `debug/test-invoice-system.php` - Testing interface

## Testing & Validation

### Test Page
Access `/debug/test-invoice-system.php` (admin only) to verify:
- Class loading and initialization
- Stripe configuration validation
- AJAX endpoint registration
- Page integration testing
- Invoice retrieval functionality

### Manual Testing Checklist
1. **Create Test Subscription**
   - Use Stripe test mode
   - Create subscription for test user
   - Verify invoice appears in Stripe Dashboard

2. **Test Customer Interface**
   - Login as customer with subscription
   - Navigate to subscription page
   - Verify invoice list displays
   - Test PDF download functionality
   - Test online invoice viewing

3. **Test Admin Interface**
   - Access NORDBOOKING Admin
   - Go to Subscription Management
   - Click "View Invoices" for a customer
   - Verify modal displays correctly
   - Test admin invoice actions

## Troubleshooting

### Common Issues

#### No Invoices Showing
**Symptoms**: Invoice list is empty or shows "No invoices found"
**Solutions**:
1. Check Stripe configuration is correct
2. Verify user has paid invoices in Stripe
3. Check browser console for JavaScript errors
4. Verify user has active subscription

#### PDF Download Not Working
**Symptoms**: PDF link doesn't work or shows error
**Solutions**:
1. Verify Stripe API keys are correct
2. Check if invoice exists in Stripe Dashboard
3. Verify user owns the invoice
4. Check Stripe invoice has PDF generated

#### Admin Modal Not Loading
**Symptoms**: Admin invoice modal doesn't open or shows errors
**Solutions**:
1. Check admin permissions (`manage_options`)
2. Verify AJAX endpoints are registered
3. Check browser console for JavaScript errors
4. Verify nonce validation is working

### Debug Information
Enable WordPress debug logging:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check logs for:
- Stripe API errors
- AJAX endpoint errors
- Authentication failures
- Invoice retrieval issues

## Future Enhancements

### Planned Features
1. **Invoice Filtering**: Filter by date range, amount, status
2. **Bulk Downloads**: Download multiple invoices as ZIP
3. **Email Integration**: Email invoice summaries to customers
4. **Advanced Analytics**: Revenue charts and payment method breakdown
5. **Notification System**: Email notifications for new invoices

### API Extensions
1. **REST API**: RESTful endpoints for invoice management
2. **Webhook Integration**: Custom webhook notifications
3. **Third-party Integration**: Connect with accounting systems
4. **Mobile Support**: Mobile app API endpoints

## Security Considerations

### Data Protection
- All invoice data retrieved from Stripe (no local storage of sensitive data)
- Strict ownership verification before providing access
- Secure AJAX endpoints with proper authentication
- Input sanitization and output escaping

### Access Control
- Customer can only access their own invoices
- Admin access requires `manage_options` capability
- All requests protected with WordPress nonces
- Rate limiting on invoice retrieval requests

### Compliance
- PCI compliance maintained through Stripe
- No storage of payment card data
- Secure transmission of all data
- Audit logging for administrative actions

This invoice system provides comprehensive invoice management capabilities while maintaining security and performance standards.