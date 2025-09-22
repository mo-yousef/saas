<?php

namespace NORDBOOKING\Classes;

class StripeConfig {
    
    const OPTION_STRIPE_SETTINGS = 'nordbooking_stripe_settings';
    
    /**
     * Get Stripe settings
     */
    public static function get_settings() {
        $defaults = [
            'test_mode' => true,
            'currency' => 'usd',
            'trial_days' => 14,
            'test_publishable_key' => '',
            'test_secret_key' => '',
            'test_price_id' => '',
            'test_webhook_secret' => '',
            'live_publishable_key' => '',
            'live_secret_key' => '',
            'live_price_id' => '',
            'live_webhook_secret' => '',
        ];
        
        $settings = get_option(self::OPTION_STRIPE_SETTINGS, []);
        return wp_parse_args($settings, $defaults);
    }
    
    /**
     * Update Stripe settings
     */
    public static function update_settings($settings) {
        return update_option(self::OPTION_STRIPE_SETTINGS, $settings);
    }
    
    /**
     * Check if Stripe is configured
     */
    public static function is_configured() {
        $settings = self::get_settings();
        
        if ($settings['test_mode']) {
            return !empty($settings['test_secret_key']) && !empty($settings['test_price_id']);
        } else {
            return !empty($settings['live_secret_key']) && !empty($settings['live_price_id']);
        }
    }
    
    /**
     * Get the current secret key (test or live)
     */
    public static function get_secret_key() {
        $settings = self::get_settings();
        
        if ($settings['test_mode']) {
            return $settings['test_secret_key'];
        } else {
            return $settings['live_secret_key'];
        }
    }
    
    /**
     * Get the current publishable key (test or live)
     */
    public static function get_publishable_key() {
        $settings = self::get_settings();
        
        if ($settings['test_mode']) {
            return $settings['test_publishable_key'];
        } else {
            return $settings['live_publishable_key'];
        }
    }
    
    /**
     * Get the current price ID (test or live)
     */
    public static function get_price_id() {
        $settings = self::get_settings();
        
        if ($settings['test_mode']) {
            return $settings['test_price_id'];
        } else {
            return $settings['live_price_id'];
        }
    }
    
    /**
     * Get the current webhook secret (test or live)
     */
    public static function get_webhook_secret() {
        $settings = self::get_settings();
        
        if ($settings['test_mode']) {
            return $settings['test_webhook_secret'];
        } else {
            return $settings['live_webhook_secret'];
        }
    }
    
    /**
     * Get trial days
     */
    public static function get_trial_days() {
        $settings = self::get_settings();
        return intval($settings['trial_days']);
    }
    
    /**
     * Get currency
     */
    public static function get_currency() {
        $settings = self::get_settings();
        return $settings['currency'];
    }
    
    /**
     * Check if in test mode
     */
    public static function is_test_mode() {
        $settings = self::get_settings();
        return $settings['test_mode'];
    }
    
    /**
     * Initialize test settings with placeholder values
     */
    public static function initialize_test_settings() {
        $settings = [
            'test_mode' => true,
            'currency' => 'usd',
            'trial_days' => 14,
            'test_publishable_key' => 'pk_test_placeholder',
            'test_secret_key' => 'sk_test_placeholder',
            'test_price_id' => 'price_placeholder',
            'test_webhook_secret' => 'whsec_placeholder',
            'live_publishable_key' => '',
            'live_secret_key' => '',
            'live_price_id' => '',
            'live_webhook_secret' => '',
        ];
        
        return self::update_settings($settings);
    }
    
    /**
     * Test Stripe connection
     */
    public static function test_stripe_connection() {
        if (!self::is_configured()) {
            return [
                'success' => false,
                'message' => 'Stripe is not properly configured. Please check your API keys and Price ID.'
            ];
        }
        
        try {
            // Include Stripe PHP library
            if (!class_exists('\Stripe\Stripe')) {
                $stripe_path = NORDBOOKING_THEME_DIR . 'vendor/stripe/stripe-php/init.php';
                if (file_exists($stripe_path)) {
                    require_once $stripe_path;
                } else {
                    return [
                        'success' => false,
                        'message' => 'Stripe PHP library not found. Please run composer install.'
                    ];
                }
            }
            
            \Stripe\Stripe::setApiKey(self::get_secret_key());
            
            // Test the connection by retrieving account info
            $account = \Stripe\Account::retrieve();
            
            return [
                'success' => true,
                'message' => 'Stripe connection successful! Account: ' . $account->display_name
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Stripe connection failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Check if API keys are configured but Price ID is missing
     */
    public static function needs_price_id() {
        $settings = self::get_settings();
        
        if ($settings['test_mode']) {
            $has_keys = !empty($settings['test_secret_key']) && !empty($settings['test_publishable_key']);
            $has_price = !empty($settings['test_price_id']) && strpos($settings['test_price_id'], 'placeholder') === false;
        } else {
            $has_keys = !empty($settings['live_secret_key']) && !empty($settings['live_publishable_key']);
            $has_price = !empty($settings['live_price_id']);
        }
        
        return $has_keys && !$has_price;
    }
    
    /**
     * Check if API keys are configured
     */
    public static function has_api_keys() {
        $settings = self::get_settings();
        
        if ($settings['test_mode']) {
            return !empty($settings['test_secret_key']) && !empty($settings['test_publishable_key']);
        } else {
            return !empty($settings['live_secret_key']) && !empty($settings['live_publishable_key']);
        }
    }
    
    /**
     * Get Stripe configuration status for admin notices
     */
    public static function get_configuration_status() {
        $settings = self::get_settings();
        
        if (!self::is_configured()) {
            return [
                'status' => 'incomplete',
                'message' => 'Stripe configuration is incomplete. Please configure your API keys and Price ID.',
                'missing' => []
            ];
        }
        
        // Check for placeholder values
        $missing = [];
        if ($settings['test_mode']) {
            if (strpos($settings['test_secret_key'], 'placeholder') !== false) {
                $missing[] = 'Test Secret Key';
            }
            if (strpos($settings['test_price_id'], 'placeholder') !== false) {
                $missing[] = 'Test Price ID';
            }
        } else {
            if (empty($settings['live_secret_key'])) {
                $missing[] = 'Live Secret Key';
            }
            if (empty($settings['live_price_id'])) {
                $missing[] = 'Live Price ID';
            }
        }
        
        if (!empty($missing)) {
            return [
                'status' => 'incomplete',
                'message' => 'Stripe configuration incomplete. Missing: ' . implode(', ', $missing),
                'missing' => $missing
            ];
        }
        
        return [
            'status' => 'complete',
            'message' => 'Stripe is properly configured.',
            'missing' => []
        ];
    }
    
    /**
     * Validate a Stripe Price ID format
     */
    public static function is_valid_price_id($price_id) {
        if (empty($price_id)) {
            return false;
        }
        
        // Check if it's a placeholder
        if (strpos($price_id, 'placeholder') !== false) {
            return false;
        }
        
        // Check if it starts with 'price_'
        return strpos($price_id, 'price_') === 0;
    }
    
    /**
     * Get validation message for Price ID
     */
    public static function get_price_id_validation_message($price_id) {
        if (empty($price_id)) {
            return 'Price ID is required for subscription billing.';
        }
        
        if (strpos($price_id, 'placeholder') !== false) {
            return 'Please replace the placeholder with your actual Stripe Price ID.';
        }
        
        if (strpos($price_id, 'prod_') === 0) {
            return 'This appears to be a Product ID, not a Price ID. Please use the Price ID from your Stripe Dashboard.';
        }
        
        if (strpos($price_id, 'price_') !== 0) {
            return 'Price ID should start with "price_". Please check your Stripe Dashboard.';
        }
        
        return ''; // Valid price ID
    }
}
