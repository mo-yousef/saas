<?php
/**
 * Standalone Invoice Generator for NORDBOOKING
 * This file creates a completely separate invoice page that bypasses WordPress dashboard
 *
 * @package NORDBOOKING
 */

// Load WordPress
require_once('wp-config.php');

// Security check - ensure user is logged in and has permission
if (!is_user_logged_in()) {
    wp_die('Access denied. Please log in.');
}

// Get booking ID from URL
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
if (!$booking_id) {
    wp_die('Invalid booking ID.');
}

// Get current user
$current_user_id = get_current_user_id();

// Initialize managers
$bookings_manager = new \NORDBOOKING\Classes\Bookings();
$currency_symbol = '$'; // Default currency

// Get booking owner ID
$booking_owner_id = $bookings_manager->get_booking_owner_id($booking_id);
if ($booking_owner_id === null) {
    wp_die('Booking not found.');
}

// Permission check
$can_view = false;
if (NORDBOOKING\Classes\Auth::is_user_business_owner($current_user_id)) {
    if ($current_user_id === $booking_owner_id) {
        $can_view = true;
    }
} elseif (NORDBOOKING\Classes\Auth::is_user_worker($current_user_id)) {
    $worker_owner_id = NORDBOOKING\Classes\Auth::get_business_owner_id_for_worker($current_user_id);
    $booking = $bookings_manager->get_booking($booking_id, $booking_owner_id);
    if ($worker_owner_id && $worker_owner_id === $booking_owner_id && $booking && (int)$booking['assigned_staff_id'] === $current_user_id) {
        $can_view = true;
    }
}

if (!$can_view) {
    wp_die('You do not have permission to view this invoice.');
}

// Get booking data
$booking = $bookings_manager->get_booking($booking_id, $booking_owner_id);
if (!$booking) {
    wp_die('Booking not found or access denied.');
}

// Get business settings
try {
    $settings_manager = new \NORDBOOKING\Classes\Settings();
    $business_settings = $settings_manager->get_business_settings($booking_owner_id);
    
    // Ensure we have the required business settings with fallbacks
    $business_settings = array_merge([
        'biz_name' => get_bloginfo('name') ?: 'Your Business',
        'biz_email' => get_option('admin_email') ?: '',
        'biz_phone' => '',
        'biz_address' => '',
        'biz_logo_url' => ''
    ], $business_settings);
    
} catch (Exception $e) {
    error_log('Invoice Generator: Error fetching business settings - ' . $e->getMessage());
    $business_settings = [
        'biz_name' => get_bloginfo('name') ?: 'Your Business',
        'biz_email' => get_option('admin_email') ?: '',
        'biz_phone' => '',
        'biz_address' => '',
        'biz_logo_url' => ''
    ];
}

// Prepare formatted data
$booking_date_formatted = !empty($booking['booking_date']) ? date_i18n(get_option('date_format'), strtotime($booking['booking_date'])) : 'N/A';
$booking_time_formatted = !empty($booking['booking_time']) ? date_i18n(get_option('time_format'), strtotime($booking['booking_time'])) : 'N/A';
$invoice_date = date_i18n(get_option('date_format'), current_time('timestamp'));

// Check if user wants to download as HTML file
$download_as_file = isset($_GET['download_as_file']) && $_GET['download_as_file'] === 'true';

// Set headers
if ($download_as_file) {
    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="Invoice-' . sanitize_file_name($booking['booking_reference']) . '.html"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
} else {
    header('Content-Type: text/html; charset=utf-8');
}

// Output the invoice HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?php echo esc_html($booking['booking_reference']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: hsl(222.2 84% 4.9%);
            background: hsl(0 0% 100%);
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100vh;
        }
        
        body {
            padding: 1rem;
        }
        
        .invoice-container {
            max-width: 210mm;
            margin: 0 auto;
            background: hsl(0 0% 100%);
            border: 1px solid hsl(214.3 31.8% 91.4%);
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
            overflow: hidden;
        }
        
        .invoice-header {
            border-bottom: 1px solid hsl(214.3 31.8% 91.4%);
            padding: 2rem;
            background: hsl(0 0% 100%);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 2rem;
        }
        
        .logo-section {
            flex: 1;
        }
        
        .logo {
            max-width: 180px;
            max-height: 80px;
            margin-bottom: 1rem;
            border-radius: 0.375rem;
        }
        
        .company-name {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: hsl(222.2 84% 4.9%);
        }
        
        .company-details {
            flex: 1;
            text-align: right;
        }
        
        .company-info {
            font-size: 0.875rem;
            line-height: 1.4;
            color: hsl(215.4 16.3% 46.9%);
        }
        
        .invoice-title {
            text-align: center;
            font-size: 2rem;
            font-weight: 700;
            margin: 1.5rem 0;
            color: hsl(222.2 84% 4.9%);
            letter-spacing: 0.025em;
        }
        
        .details-section {
            padding: 2rem;
            background: hsl(210 40% 98%);
            border-bottom: 1px solid hsl(214.3 31.8% 91.4%);
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .invoice-details, .customer-details {
            background: hsl(0 0% 100%);
            padding: 1.5rem;
            border-radius: 0.5rem;
            border: 1px solid hsl(214.3 31.8% 91.4%);
        }
        
        .section-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: hsl(222.2 84% 4.9%);
            padding-bottom: 0.5rem;
            border-bottom: 2px solid hsl(221.2 83.2% 53.3%);
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.75rem;
            padding: 0.25rem 0;
        }
        
        .detail-label {
            font-weight: 500;
            color: hsl(215.4 16.3% 46.9%);
            flex-shrink: 0;
        }
        
        .detail-value {
            color: hsl(222.2 84% 4.9%);
            text-align: right;
            margin-left: 1rem;
            font-weight: 500;
        }
        
        .services-section {
            padding: 2rem;
        }
        
        .services-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            border: 1px solid hsl(214.3 31.8% 91.4%);
            border-radius: 0.5rem;
            overflow: hidden;
        }
        
        .services-table th {
            background: hsl(210 40% 98%);
            color: hsl(222.2 84% 4.9%);
            padding: 0.75rem 1rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.875rem;
            border-bottom: 1px solid hsl(214.3 31.8% 91.4%);
        }
        
        .services-table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid hsl(214.3 31.8% 91.4%);
            vertical-align: top;
            font-size: 0.875rem;
        }
        
        .services-table tr:last-child td {
            border-bottom: none;
        }
        
        .services-table tr:nth-child(even) {
            background-color: hsl(210 40% 98%);
        }
        
        .price-cell {
            text-align: right;
            font-weight: 600;
            color: hsl(222.2 84% 4.9%);
        }
        
        .option-row td {
            padding-left: 2rem;
            font-style: italic;
            color: hsl(215.4 16.3% 46.9%);
            font-size: 0.8125rem;
        }
        
        .totals-section {
            padding: 2rem;
            background: hsl(210 40% 98%);
        }
        
        .totals-table {
            width: 100%;
            max-width: 400px;
            margin-left: auto;
            border: 1px solid hsl(214.3 31.8% 91.4%);
            border-collapse: collapse;
            border-radius: 0.5rem;
            overflow: hidden;
            background: hsl(0 0% 100%);
        }
        
        .totals-table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid hsl(214.3 31.8% 91.4%);
            font-size: 0.875rem;
        }
        
        .totals-table tr:last-child td {
            background: hsl(221.2 83.2% 53.3%);
            color: hsl(210 40% 98%);
            font-weight: 700;
            font-size: 1rem;
            border-bottom: none;
        }
        
        .total-label {
            text-align: left;
            font-weight: 600;
        }
        
        .total-amount {
            text-align: right;
            font-weight: 600;
        }
        
        .footer {
            border-top: 1px solid hsl(214.3 31.8% 91.4%);
            padding: 2rem;
            text-align: center;
            background: hsl(210 40% 98%);
        }
        
        .thank-you {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: hsl(222.2 84% 4.9%);
        }
        
        .footer-info {
            font-size: 0.875rem;
            color: hsl(215.4 16.3% 46.9%);
        }
        
        .print-button {
            position: fixed;
            top: 1rem;
            right: 1rem;
            background: hsl(221.2 83.2% 53.3%);
            color: hsl(210 40% 98%);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            z-index: 9999;
            box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
            transition: all 0.2s ease;
        }
        
        .print-button:hover {
            background: hsl(221.2 83.2% 48%);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
        }
        
        .special-instructions {
            padding: 2rem;
            border-top: 1px solid hsl(214.3 31.8% 91.4%);
        }
        
        .special-instructions-content {
            background: hsl(210 40% 98%);
            padding: 1rem;
            border: 1px solid hsl(214.3 31.8% 91.4%);
            border-radius: 0.375rem;
            font-size: 0.875rem;
            color: hsl(215.4 16.3% 46.9%);
        }
        
        @media print {
            html, body {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                height: 100% !important;
            }
            
            body {
                padding: 0 !important;
            }
            
            .invoice-container {
                border: none;
                box-shadow: none;
                border-radius: 0;
                max-width: none;
                margin: 0;
            }
            
            .print-button {
                display: none !important;
            }
            
            .details-section,
            .totals-section,
            .footer {
                background: white !important;
            }
            
            .services-table tr:nth-child(even) {
                background-color: #f8f9fa !important;
            }
        }
        
        @media (max-width: 768px) {
            body {
                padding: 0.5rem !important;
            }
            
            .details-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .header-content {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }
            
            .company-details {
                text-align: center;
            }
            
            .invoice-header,
            .details-section,
            .services-section,
            .totals-section,
            .special-instructions,
            .footer {
                padding: 1rem;
            }
            
            .services-table {
                font-size: 0.8125rem;
            }
            
            .services-table th,
            .services-table td {
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <button class="print-button" onclick="window.print()">Print</button>
    
    <div class="invoice-container">
        <div class="invoice-header">
            <div class="header-content">
                <div class="logo-section">
                    <?php if (!empty($business_settings['biz_logo_url'])): ?>
                        <img src="<?php echo esc_url($business_settings['biz_logo_url']); ?>" alt="<?php echo esc_attr($business_settings['biz_name']); ?> Logo" class="logo">
                    <?php endif; ?>
                    <div class="company-name"><?php echo esc_html($business_settings['biz_name'] ?: 'Your Business Name'); ?></div>
                </div>
                <div class="company-details">
                    <div class="company-info">
                        <?php if (!empty($business_settings['biz_address'])): ?>
                            <div><?php echo nl2br(esc_html($business_settings['biz_address'])); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($business_settings['biz_phone'])): ?>
                            <div>Phone: <?php echo esc_html($business_settings['biz_phone']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($business_settings['biz_email'])): ?>
                            <div>Email: <?php echo esc_html($business_settings['biz_email']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="invoice-title">INVOICE</div>
        </div>

        <div class="details-section">
            <div class="details-grid">
                <div class="invoice-details">
                    <h3 class="section-title">Invoice Details</h3>
                    <div class="detail-item">
                        <span class="detail-label">Invoice #:</span>
                        <span class="detail-value"><?php echo esc_html($booking['booking_reference']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Invoice Date:</span>
                        <span class="detail-value"><?php echo esc_html($invoice_date); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Service Date:</span>
                        <span class="detail-value"><?php echo esc_html($booking_date_formatted); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Service Time:</span>
                        <span class="detail-value"><?php echo esc_html($booking_time_formatted); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value">
                            <?php echo esc_html(ucfirst(str_replace('-', ' ', $booking['status']))); ?>
                        </span>
                    </div>
                </div>
                
                <div class="customer-details">
                    <h3 class="section-title">Bill To</h3>
                    <div class="detail-item">
                        <span class="detail-label">Customer:</span>
                        <span class="detail-value"><strong><?php echo esc_html($booking['customer_name']); ?></strong></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Email:</span>
                        <span class="detail-value"><?php echo esc_html($booking['customer_email']); ?></span>
                    </div>
                    <?php if (!empty($booking['customer_phone'])): ?>
                    <div class="detail-item">
                        <span class="detail-label">Phone:</span>
                        <span class="detail-value"><?php echo esc_html($booking['customer_phone']); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="detail-item">
                        <span class="detail-label">Service Address:</span>
                        <span class="detail-value"><?php echo nl2br(esc_html($booking['service_address'])); ?><?php if (!empty($booking['zip_code'])) { echo '<br>' . esc_html($booking['zip_code']); } ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="services-section">
            <h3 class="section-title">Services & Details</h3>
            <table class="services-table">
                <thead>
                    <tr>
                        <th style="width: 50%;">Service / Option</th>
                        <th class="price-cell" style="width: 25%;">Unit Price</th>
                        <th class="price-cell" style="width: 25%;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $subtotal = 0;
                    if (isset($booking['items']) && is_array($booking['items']) && !empty($booking['items'])) {
                        foreach ($booking['items'] as $item) {
                            $subtotal += floatval($item['item_total_price']);
                    ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($item['service_name']); ?></strong>
                                    <?php if (!empty($item['service_description'])): ?>
                                        <br><small style="color: hsl(215.4 16.3% 46.9%);"><?php echo esc_html($item['service_description']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="price-cell"><?php echo esc_html($currency_symbol . number_format_i18n(floatval($item['service_price']), 2)); ?></td>
                                <td class="price-cell"><strong><?php echo esc_html($currency_symbol . number_format_i18n(floatval($item['item_total_price']), 2)); ?></strong></td>
                            </tr>
                    <?php
                            // Display selected options if they exist
                            $selected_options_raw = $item['selected_options'] ?? [];
                            if (is_string($selected_options_raw)) {
                                $selected_options = json_decode($selected_options_raw, true);
                                if (json_last_error() !== JSON_ERROR_NONE) {
                                    $selected_options = [];
                                }
                            } else {
                                $selected_options = is_array($selected_options_raw) ? $selected_options_raw : [];
                            }
                            
                            if (!empty($selected_options) && is_array($selected_options)) {
                                foreach ($selected_options as $option_key => $option_data) {
                                    $option_name = '';
                                    $option_value = '';
                                    $option_price = 0;
                                    
                                    if (is_array($option_data) && isset($option_data['name'])) {
                                        $option_name = $option_data['name'];
                                        $value_from_db = $option_data['value'] ?? '';
                                        
                                        if (is_string($value_from_db)) {
                                            $decoded_value = json_decode($value_from_db, true);
                                            if (is_array($decoded_value) && isset($decoded_value['name']) && isset($decoded_value['value'])) {
                                                $option_name = $decoded_value['name'];
                                                $option_value = $decoded_value['value'];
                                                $option_price = isset($decoded_value['price']) ? floatval($decoded_value['price']) : 0;
                                            } else {
                                                $option_value = $value_from_db;
                                                $option_price = isset($option_data['price']) ? floatval($option_data['price']) : 0;
                                            }
                                        }
                                    }
                                    
                                    if (!empty($option_name)) {
                    ?>
                                        <tr class="option-row">
                                            <td>
                                                <span style="margin-left: 20px;">└ <?php echo esc_html($option_name); ?>: <?php echo esc_html($option_value); ?></span>
                                            </td>
                                            <td class="price-cell">
                                                <?php if ($option_price != 0) { 
                                                    echo esc_html(($option_price > 0 ? '+' : '') . $currency_symbol . number_format_i18n(abs($option_price), 2)); 
                                                } else {
                                                    echo '—';
                                                } ?>
                                            </td>
                                            <td class="price-cell">—</td>
                                        </tr>
                    <?php
                                    }
                                }
                            }
                        }
                    } else {
                        // Fallback if no items are found
                    ?>
                        <tr>
                            <td colspan="3" style="text-align: center; padding: 20px; color: #6c757d;">
                                <em>No service details available</em>
                            </td>
                        </tr>
                    <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td class="total-label">Subtotal:</td>
                    <td class="total-amount"><?php echo esc_html($currency_symbol . number_format_i18n($subtotal, 2)); ?></td>
                </tr>
                <?php if (floatval($booking['discount_amount']) > 0): ?>
                <tr>
                    <td class="total-label">Discount:</td>
                    <td class="total-amount">- <?php echo esc_html($currency_symbol . number_format_i18n(floatval($booking['discount_amount']), 2)); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td class="total-label">Total Amount:</td>
                    <td class="total-amount"><?php echo esc_html($currency_symbol . number_format_i18n(floatval($booking['total_price']), 2)); ?></td>
                </tr>
            </table>
        </div>

        <?php if (!empty($booking['special_instructions'])): ?>
        <div class="special-instructions">
            <h3 class="section-title">Special Instructions</h3>
            <div class="special-instructions-content">
                <?php echo nl2br(esc_html($booking['special_instructions'])); ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="footer">
            <div class="footer-content">
                <div class="thank-you">Thank you for choosing <?php echo esc_html($business_settings['biz_name'] ?: 'our services'); ?>!</div>
                <div class="footer-info">
                    We appreciate your business and look forward to serving you again.
                    <?php if (!empty($business_settings['biz_email'])): ?>
                        <br>For any questions, please contact us at <?php echo esc_html($business_settings['biz_email']); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-focus print dialog when page loads (only if not downloading as file)
        window.addEventListener('load', function() {
            // Small delay to ensure page is fully rendered
            setTimeout(function() {
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.get('download_invoice') === 'true' && urlParams.get('download_as_file') !== 'true') {
                    // Show print dialog automatically for print/PDF generation
                    window.print();
                }
            }, 500);
        });
        
        // Handle print button click
        const printButton = document.querySelector('.print-button');
        if (printButton) {
            printButton.addEventListener('click', function() {
                window.print();
            });
        }
        
        // Add keyboard shortcut for printing
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
        });
    </script>
</body>
</html>