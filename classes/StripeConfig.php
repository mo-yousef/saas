<?php

namespace NORDBOOKING\Classes;

class StripeConfig {
    
    const OPTION_STRIPE_SETTINGS = 'nordbooking_stripe_settings';
    
    /**
     * Get Stripe configuration settings
     */
    public static function get_settings() {
        $defaults = [
            'test_mode' => true,
            'test_publishable_key' => '',
            'test_secret_key' => '',
            'test_webhook_secret' => '',
            'live_publishable_key' => '',
            'live_secret_key' => '',
            'live_webhook_secret' => '',
            'price_id' => '',
            'currency' => 'usd',
            'trial_days' => 7,
        ];
        
        $settings = get_option(self::OPTION_STRIPE_SETTINGS, $defaults);
        return wp_parse_args($settings, $defaults);
    }
    
    /**
     * Update Stripe settings
     */
    public static function update_settings($settings) {
        return update_option(self::OPTION_STRIPE_SETTINGS, $settings);
    }
    
    /**
     * Get the appropriate Stripe secret key based on test mode
     */
    public static function get_secret_key() {
        $settings = self::get_settings();
        return $settings['test_mode'] ? $settings['test_secret_key'] : $settings['live_secret_key'];
    }
    
    /**
     * Get the appropriate Stripe publishable key based on test mode
     */
    public static function get_publishable_key() {
        $settings = self::get_settings();
        return $settings['test_mode'] ? $settings['test_publishable_key'] : $settings['live_publishable_key'];
    }
    
    /**
     * Get the appropriate webhook secret based on test mode
     */
    public static function get_webhook_secret() {
        $settings = self::get_settings();
        return $settings['test_mode'] ? $settings['test_webhook_secret'] : $settings['live_webhook_secret'];
    }
    
    /**
     * Get the price ID for subscriptions
     */
    public static function get_price_id() {
        $settings = self::get_settings();
        return $settings['price_id'];
    }
    
    /**
     * Check if Stripe is properly configured
     */
    public static function is_configured() {
        $secret_key = self::get_secret_key();
        $price_id = self::get_price_id();
        
        return !empty($secret_key) && !empty($price_id);
    }
    
    /**
     * Get test mode status
     */
    public static function is_test_mode() {
        $settings = self::get_settings();
        return $settings['test_mode'];
    }
    
    /**
     * Get currency
     */
    public static function get_currency() {
        $settings = self::get_settings();
        return $settings['currency'];
    }
    
    /**
     * Get trial days
     */
    public static function get_trial_days() {
        $settings = self::get_settings();
        return intval($settings['trial_days']);
    }
}