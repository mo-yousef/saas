<?php
/**
 * Invoice Generator for NORDBOOKING
 *
 * This file is responsible for generating a PDF invoice for a given booking.
 * It is included by page-booking-single.php when the 'download_invoice' parameter is set.
 *
 * @package NORDBOOKING
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// The necessary variables ($single_booking_id, $bookings_manager, $currency_symbol, $current_user_id, $booking, $booking_owner_id_for_fetch)
// are available from the parent script (page-booking-single.php).

// 1. Include the mPDF library
$mpdf_path = get_template_directory() . '/lib/mpdf/03-mPDF-v6.1.0-without-examples-and-fonts/mpdf.php';
if ( ! file_exists( $mpdf_path ) ) {
    wp_die( 'mPDF library not found. Please check the installation.' );
}
require_once $mpdf_path;

// 2. Security checks are implicitly handled by the calling script (page-booking-single.php)
// which already verified user permissions and booking ownership. We can add an extra check here for safety.
if ( ! isset( $booking ) || ! is_array( $booking ) ) {
    wp_die( 'Booking data is not available or you do not have permission to view this invoice.' );
}

// 3. Instantiate the Settings class
$settings_manager = new \NORDBOOKING\Classes\Settings();

// 4. Fetch business settings
$business_settings = $settings_manager->get_business_settings( $booking_owner_id_for_fetch );

// 5. Generate the HTML for the invoice

// Start output buffering to capture HTML
ob_start();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice <?php echo esc_html( $booking['booking_reference'] ); ?></title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
            color: #333;
        }
        .invoice-container {
            width: 100%;
            margin: 0 auto;
        }
        .invoice-header {
            padding: 20px;
            border-bottom: 2px solid #eee;
            overflow: auto; /* To clear floats */
        }
        .logo {
            float: left;
            max-width: 150px;
            max-height: 70px;
        }
        .company-details {
            float: right;
            text-align: right;
        }
        .invoice-details, .customer-details {
            width: 48%;
            display: inline-block;
            vertical-align: top;
            padding: 10px 0;
        }
        .customer-details {
            text-align: right;
        }
        h1, h2, h3, h4 {
            margin: 0;
            padding: 0;
        }
        h2 {
            font-size: 18px;
            margin-bottom: 10px;
        }
        .details-section {
            padding: 20px;
            border-bottom: 2px solid #eee;
        }
        .services-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .services-table th, .services-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .services-table th {
            background-color: #f2f2f2;
        }
        .price-cell {
            text-align: right;
        }
        .totals-table {
            width: 40%;
            float: right;
            margin-top: 20px;
        }
        .totals-table td {
            padding: 5px;
        }
        .totals-table .label {
            text-align: right;
            font-weight: bold;
        }
        .totals-table .amount {
            text-align: right;
        }
        .footer {
            margin-top: 30px;
            padding: 20px;
            font-size: 10px;
            text-align: center;
            border-top: 1px solid #eee;
        }
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="invoice-header">
            <?php if ( ! empty( $business_settings['biz_logo_url'] ) ) : ?>
                <img src="<?php echo esc_url( $business_settings['biz_logo_url'] ); ?>" alt="Company Logo" class="logo">
            <?php endif; ?>
            <div class="company-details">
                <h2><?php echo esc_html( $business_settings['biz_name'] ); ?></h2>
                <p><?php echo nl2br( esc_html( $business_settings['biz_address'] ) ); ?></p>
                <p><?php echo esc_html( $business_settings['biz_phone'] ); ?></p>
                <p><?php echo esc_html( $business_settings['biz_email'] ); ?></p>
            </div>
        </div>

        <div class="details-section clearfix">
            <div class="invoice-details">
                <h3>Invoice</h3>
                <p><strong>Invoice #:</strong> <?php echo esc_html( $booking['booking_reference'] ); ?></p>
                <p><strong>Date:</strong> <?php echo date_i18n( get_option('date_format'), current_time('timestamp') ); ?></p>
                <p><strong>Booking Date:</strong> <?php echo date_i18n( get_option('date_format'), strtotime( $booking['booking_date'] ) ); ?></p>
                <p><strong>Status:</strong> <?php echo esc_html( ucfirst( $booking['status'] ) ); ?></p>
            </div>
            <div class="customer-details">
                <h3>Bill To</h3>
                <p><strong><?php echo esc_html( $booking['customer_name'] ); ?></strong></p>
                <p><?php echo nl2br( esc_html( $booking['service_address'] ) ); ?></p>
                <p><?php echo esc_html( $booking['customer_phone'] ); ?></p>
                <p><?php echo esc_html( $booking['customer_email'] ); ?></p>
            </div>
        </div>

        <div class="services-section">
            <table class="services-table">
                <thead>
                    <tr>
                        <th>Service / Option</th>
                        <th class="price-cell">Price</th>
                        <th class="price-cell">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $subtotal = 0;
                    if ( isset( $booking['items'] ) && is_array( $booking['items'] ) ) {
                        foreach ( $booking['items'] as $item ) {
                            $subtotal += floatval( $item['item_total_price'] );
                    ?>
                            <tr>
                                <td><?php echo esc_html( $item['service_name'] ); ?></td>
                                <td class="price-cell"><?php echo esc_html( $currency_symbol . number_format_i18n( floatval( $item['service_price'] ), 2 ) ); ?></td>
                                <td class="price-cell"><?php echo esc_html( $currency_symbol . number_format_i18n( floatval( $item['item_total_price'] ), 2 ) ); ?></td>
                            </tr>
                    <?php
                            // Display selected options if they exist
                            $selected_options = is_string($item['selected_options']) ? json_decode($item['selected_options'], true) : $item['selected_options'];
                            if ( ! empty( $selected_options ) && is_array( $selected_options ) ) {
                                foreach ( $selected_options as $option ) {
                                    $option_price = isset($option['price']) ? floatval($option['price']) : 0;
                    ?>
                                    <tr class="option-row">
                                        <td style="padding-left: 20px;">└ <?php echo esc_html( $option['name'] . ': ' . $option['value'] ); ?></td>
                                        <td class="price-cell"><?php if ($option_price != 0) { echo esc_html( ($option_price > 0 ? '+' : '') . $currency_symbol . number_format_i18n( $option_price, 2 ) ); } ?></td>
                                        <td class="price-cell"></td>
                                    </tr>
                    <?php
                                }
                            }
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="totals-section clearfix">
            <table class="totals-table">
                <tr>
                    <td class="label">Subtotal:</td>
                    <td class="amount"><?php echo esc_html( $currency_symbol . number_format_i18n( $subtotal, 2 ) ); ?></td>
                </tr>
                <?php if ( floatval( $booking['discount_amount'] ) > 0 ) : ?>
                <tr>
                    <td class="label">Discount:</td>
                    <td class="amount">- <?php echo esc_html( $currency_symbol . number_format_i18n( floatval( $booking['discount_amount'] ), 2 ) ); ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td class="label" style="font-size: 14px;">Total:</td>
                    <td class="amount" style="font-size: 14px; font-weight: bold;"><?php echo esc_html( $currency_symbol . number_format_i18n( floatval( $booking['total_price'] ), 2 ) ); ?></td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <p>Thank you for your business!</p>
            <p><?php echo esc_html( $business_settings['biz_name'] ); ?></p>
        </div>
    </div>
</body>
</html>

<?php
// Get the captured HTML from the buffer
$html = ob_get_clean();

// 6. Use mPDF to generate the PDF
try {
    $mpdf = new mPDF( [
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_left' => 10,
        'margin_right' => 10,
        'margin_top' => 10,
        'margin_bottom' => 10,
    ] );

    // Write HTML to PDF
    $mpdf->WriteHTML( $html );

    // 7. Output the PDF to the browser for download
    $mpdf->Output( 'Invoice-' . $booking['booking_reference'] . '.pdf', 'D' );
    exit;

} catch ( \MpdfException $e ) {
    wp_die( 'mPDF Error: ' . $e->getMessage() );
}
