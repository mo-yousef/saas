<?php
/**
 * Class Utils
 * Provides utility/helper functions for the theme.
 * @package NORDBOOKING\Classes
 */
namespace NORDBOOKING\Classes;

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
        if (is_null($decimals)) {
            // Example: $decimals = intval(get_option('nordbooking_currency_decimals', 2));
            $decimals = 2;
        }
        if (is_null($decimal_sep)) {
            // Example: $decimal_sep = get_option('nordbooking_currency_decimal_sep', '.');
            $decimal_sep = '.';
        }
        if (is_null($thousand_sep)) {
            // Example: $thousand_sep = get_option('nordbooking_currency_thousand_sep', ',');
            $thousand_sep = ',';
        }

        if ($currency_symbol === null || $currency_pos === null) {
            $settings = new \NORDBOOKING\Classes\Settings();
            // TODO: get_current_user_id() might not be available in all contexts.
            // Consider passing user_id as a parameter or using a fallback.
            $user_id = function_exists('get_current_user_id') ? get_current_user_id() : null;
            $biz_currency_code = $settings->get_setting($user_id, 'biz_currency_code', 'USD');
            if ($currency_symbol === null) {
                $currency_symbol = self::get_currency_symbol($biz_currency_code);
            }
            if ($currency_pos === null) {
                $currency_pos = self::get_currency_position($biz_currency_code);
            }
        }

        $amount = floatval($amount);
        $formatted_number = number_format($amount, $decimals, $decimal_sep, $thousand_sep);

        if ($currency_pos === 'before') {
            return $currency_symbol . $formatted_number;
        } else {
            return $formatted_number . $currency_symbol;
        } // This closing brace was missing
    } // Add this closing brace for the format_currency method

    public static function get_currency_symbol(string $currency_code): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'CAD' => '$',
            'AUD' => '$',
        ];
        return $symbols[$currency_code] ?? $currency_code;
        }

    public static function get_currency_position(string $currency_code): string
    {
        $positions = [
            'USD' => 'before',
            'EUR' => 'after',
            'GBP' => 'before',
            'JPY' => 'before',
            'CAD' => 'before',
            'AUD' => 'before',
        ];
        return $positions[$currency_code] ?? 'before';
    }

    public static function sanitize_svg(string $svg_content): string {
        // Remove <script> tags
        $svg_content = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $svg_content);
        // Remove on* attributes
        $svg_content = preg_replace('/on[a-zA-Z]+\s*=\s*".*?"/i', '', $svg_content);
        $svg_content = preg_replace("/on[a-zA-Z]+\s*=\s*'.*?'/i", '', $svg_content);
        $svg_content = preg_replace('/on[a-zA-Z]+\s*=\s*[^>\s]+/i', '', $svg_content);


        $allowed_svg_tags = [
            'svg'   => [
                'width'   => true, 'height'  => true, 'viewbox' => true, 'xmlns'   => true, 'fill' => true, 'style' => true, // style for basic display none etc.
            ],
            'path'  => ['d' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'style' => true],
            'circle' => ['cx' => true, 'cy' => true, 'r' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'style' => true],
            'rect'  => [
                'x' => true, 'y' => true, 'width'   => true, 'height'  => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'rx' => true, 'ry' => true, 'style' => true
            ],
            'g'     => ['fill' => true, 'stroke' => true, 'transform' => true, 'style' => true],
            'line'  => [
                'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'stroke' => true, 'stroke-width' => true, 'style' => true
            ],
            'polyline' => ['points' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'style' => true],
            'polygon'  => ['points' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'style' => true],
            'title'    => [], // Allow title for accessibility
            'desc'     => [], // Allow desc for accessibility
            'defs'     => [],
            'symbol'   => ['id' => true, 'viewbox' => true],
            'use'      => ['href' => true, 'xlink:href' => true, 'x' => true, 'y' => true, 'width' => true, 'height' => true, 'fill' => true, 'stroke' => true],
            'style'    => ['type' => true], // Allow style tag but its content will be filtered by wp_kses_hair
        ];
        // Note: Content of <style> tag is tricky. wp_kses might not deeply sanitize it.
        // A better approach for <style> would be to parse it and allow only safe CSS properties.
        // For now, this relies on wp_kses_hair for attribute filtering within the style tag itself if any.

        return wp_kses( $svg_content, $allowed_svg_tags );
    }
}
