<?php
/**
 * Class Utils
 * Provides utility/helper functions for the theme.
 * @package MoBooking\Classes
 */
namespace MoBooking\Classes;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Utils {
    public static function example_static_method() {
        // Example static utility method
        return 'Hello from Utils!';
    }

    /**
     * Formats a given number as currency.
     *
     * @param float $amount The amount to format.
     * @param string $currency_symbol The currency symbol. Defaults to '$'.
     * @param string $currency_pos Position of the currency symbol (before/after). Defaults to 'before'.
     * @param int $decimals Number of decimal points. Defaults to 2.
     * @param string $decimal_sep Decimal separator. Defaults to '.'.
     * @param string $thousand_sep Thousand separator. Defaults to ','.
     * @return string Formatted currency string.
     */
    public static function format_currency(
        $amount,
        $currency_symbol = null,
        $currency_pos = null,
        $decimals = null,
        $decimal_sep = null,
        $thousand_sep = null
    ) {
        // Try to get WordPress locale settings for currency
        // These could be from a plugin's settings eventually
        if (is_null($currency_symbol)) {
            // Simplistic approach; a real app would have a setting for this.
            // Example: $currency_symbol = get_option('mobooking_currency_symbol', '$');
            $currency_symbol = '$';
        }
        if (is_null($currency_pos)) {
            // Example: $currency_pos = get_option('mobooking_currency_pos', 'before');
            $currency_pos = 'before'; // 'before' or 'after'
        }
        if (is_null($decimals)) {
            // Example: $decimals = intval(get_option('mobooking_currency_decimals', 2));
            $decimals = 2;
        }
        if (is_null($decimal_sep)) {
            // Example: $decimal_sep = get_option('mobooking_currency_decimal_sep', '.');
            $decimal_sep = '.';
        }
        if (is_null($thousand_sep)) {
            // Example: $thousand_sep = get_option('mobooking_currency_thousand_sep', ',');
            $thousand_sep = ',';
        }

        $amount = floatval($amount);
        $formatted_number = number_format($amount, $decimals, $decimal_sep, $thousand_sep);

        if ($currency_pos === 'before') {
            return $currency_symbol . $formatted_number;
        } else {
            return $formatted_number . $currency_symbol;
        }
    }

    // More static helper functions
}
