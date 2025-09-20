<?php

namespace NORDBOOKING\Classes\Admin;

use NORDBOOKING\Classes\StripeConfig;

class StripeSettingsPage {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'nordbooking-admin',
            __('Stripe Settings', 'NORDBOOKING'),
            __('Stripe Settings', 'NORDBOOKING'),
            'manage_options',
            'nordbooking-stripe-settings',
            array($this, 'settings_page')
        );
    }
    
    public function init_settings() {
        register_setting('nordbooking_stripe_settings', StripeConfig::OPTION_STRIPE_SETTINGS);
        
        add_settings_section(
            'nordbooking_stripe_general',
            __('General Settings', 'NORDBOOKING'),
            array($this, 'general_section_callback'),
            'nordbooking_stripe_settings'
        );
        
        add_settings_section(
            'nordbooking_stripe_test',
            __('Test Mode Settings', 'NORDBOOKING'),
            array($this, 'test_section_callback'),
            'nordbooking_stripe_settings'
        );
        
        add_settings_section(
            'nordbooking_stripe_live',
            __('Live Mode Settings', 'NORDBOOKING'),
            array($this, 'live_section_callback'),
            'nordbooking_stripe_settings'
        );
        
        // General settings
        add_settings_field(
            'test_mode',
            __('Test Mode', 'NORDBOOKING'),
            array($this, 'test_mode_callback'),
            'nordbooking_stripe_settings',
            'nordbooking_stripe_general'
        );
        
        add_settings_field(
            'currency',
            __('Currency', 'NORDBOOKING'),
            array($this, 'currency_callback'),
            'nordbooking_stripe_settings',
            'nordbooking_stripe_general'
        );
        
        add_settings_field(
            'trial_days',
            __('Trial Days', 'NORDBOOKING'),
            array($this, 'trial_days_callback'),
            'nordbooking_stripe_settings',
            'nordbooking_stripe_general'
        );
        
        // Test mode settings
        add_settings_field(
            'test_publishable_key',
            __('Test Publishable Key', 'NORDBOOKING'),
            array($this, 'test_publishable_key_callback'),
            'nordbooking_stripe_settings',
            'nordbooking_stripe_test'
        );
        
        add_settings_field(
            'test_secret_key',
            __('Test Secret Key', 'NORDBOOKING'),
            array($this, 'test_secret_key_callback'),
            'nordbooking_stripe_settings',
            'nordbooking_stripe_test'
        );
        
        add_settings_field(
            'test_webhook_secret',
            __('Test Webhook Secret', 'NORDBOOKING'),
            array($this, 'test_webhook_secret_callback'),
            'nordbooking_stripe_settings',
            'nordbooking_stripe_test'
        );
        
        // Live mode settings
        add_settings_field(
            'live_publishable_key',
            __('Live Publishable Key', 'NORDBOOKING'),
            array($this, 'live_publishable_key_callback'),
            'nordbooking_stripe_settings',
            'nordbooking_stripe_live'
        );
        
        add_settings_field(
            'live_secret_key',
            __('Live Secret Key', 'NORDBOOKING'),
            array($this, 'live_secret_key_callback'),
            'nordbooking_stripe_settings',
            'nordbooking_stripe_live'
        );
        
        add_settings_field(
            'live_webhook_secret',
            __('Live Webhook Secret', 'NORDBOOKING'),
            array($this, 'live_webhook_secret_callback'),
            'nordbooking_stripe_settings',
            'nordbooking_stripe_live'
        );
        
        add_settings_field(
            'price_id',
            __('Price ID', 'NORDBOOKING'),
            array($this, 'price_id_callback'),
            'nordbooking_stripe_settings',
            'nordbooking_stripe_live'
        );
    }
    
    public function settings_page() {
        $settings = StripeConfig::get_settings();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php if (StripeConfig::is_configured()): ?>
                <div class="notice notice-success">
                    <p><?php _e('Stripe is properly configured and ready to use.', 'NORDBOOKING'); ?></p>
                </div>
            <?php else: ?>
                <div class="notice notice-warning">
                    <p><?php _e('Stripe configuration is incomplete. Please fill in all required fields.', 'NORDBOOKING'); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="notice notice-info">
                <p><strong><?php _e('Webhook URL:', 'NORDBOOKING'); ?></strong> <code><?php echo esc_url(home_url('/stripe-webhook/')); ?></code></p>
                <p><?php _e('Add this URL to your Stripe webhook endpoints in the Stripe Dashboard.', 'NORDBOOKING'); ?></p>
            </div>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('nordbooking_stripe_settings');
                do_settings_sections('nordbooking_stripe_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    public function general_section_callback() {
        echo '<p>' . __('Configure general Stripe settings for your subscription system.', 'NORDBOOKING') . '</p>';
    }
    
    public function test_section_callback() {
        echo '<p>' . __('Test mode settings for development and testing. Use test keys from your Stripe Dashboard.', 'NORDBOOKING') . '</p>';
    }
    
    public function live_section_callback() {
        echo '<p>' . __('Live mode settings for production. Use live keys from your Stripe Dashboard.', 'NORDBOOKING') . '</p>';
    }
    
    public function test_mode_callback() {
        $settings = StripeConfig::get_settings();
        ?>
        <input type="checkbox" name="<?php echo StripeConfig::OPTION_STRIPE_SETTINGS; ?>[test_mode]" value="1" <?php checked($settings['test_mode'], 1); ?> />
        <label><?php _e('Enable test mode', 'NORDBOOKING'); ?></label>
        <p class="description"><?php _e('When enabled, test keys will be used instead of live keys.', 'NORDBOOKING'); ?></p>
        <?php
    }
    
    public function currency_callback() {
        $settings = StripeConfig::get_settings();
        $currencies = [
            'usd' => 'USD - US Dollar',
            'eur' => 'EUR - Euro',
            'gbp' => 'GBP - British Pound',
            'cad' => 'CAD - Canadian Dollar',
            'aud' => 'AUD - Australian Dollar',
        ];
        ?>
        <select name="<?php echo StripeConfig::OPTION_STRIPE_SETTINGS; ?>[currency]">
            <?php foreach ($currencies as $code => $name): ?>
                <option value="<?php echo esc_attr($code); ?>" <?php selected($settings['currency'], $code); ?>>
                    <?php echo esc_html($name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }
    
    public function trial_days_callback() {
        $settings = StripeConfig::get_settings();
        ?>
        <input type="number" name="<?php echo StripeConfig::OPTION_STRIPE_SETTINGS; ?>[trial_days]" value="<?php echo esc_attr($settings['trial_days']); ?>" min="0" max="365" />
        <p class="description"><?php _e('Number of days for the free trial period.', 'NORDBOOKING'); ?></p>
        <?php
    }
    
    public function test_publishable_key_callback() {
        $settings = StripeConfig::get_settings();
        ?>
        <input type="text" name="<?php echo StripeConfig::OPTION_STRIPE_SETTINGS; ?>[test_publishable_key]" value="<?php echo esc_attr($settings['test_publishable_key']); ?>" class="regular-text" placeholder="pk_test_..." />
        <p class="description"><?php _e('Your Stripe test publishable key (starts with pk_test_).', 'NORDBOOKING'); ?></p>
        <?php
    }
    
    public function test_secret_key_callback() {
        $settings = StripeConfig::get_settings();
        ?>
        <input type="password" name="<?php echo StripeConfig::OPTION_STRIPE_SETTINGS; ?>[test_secret_key]" value="<?php echo esc_attr($settings['test_secret_key']); ?>" class="regular-text" placeholder="sk_test_..." />
        <p class="description"><?php _e('Your Stripe test secret key (starts with sk_test_).', 'NORDBOOKING'); ?></p>
        <?php
    }
    
    public function test_webhook_secret_callback() {
        $settings = StripeConfig::get_settings();
        ?>
        <input type="password" name="<?php echo StripeConfig::OPTION_STRIPE_SETTINGS; ?>[test_webhook_secret]" value="<?php echo esc_attr($settings['test_webhook_secret']); ?>" class="regular-text" placeholder="whsec_..." />
        <p class="description"><?php _e('Your Stripe test webhook secret (starts with whsec_).', 'NORDBOOKING'); ?></p>
        <?php
    }
    
    public function live_publishable_key_callback() {
        $settings = StripeConfig::get_settings();
        ?>
        <input type="text" name="<?php echo StripeConfig::OPTION_STRIPE_SETTINGS; ?>[live_publishable_key]" value="<?php echo esc_attr($settings['live_publishable_key']); ?>" class="regular-text" placeholder="pk_live_..." />
        <p class="description"><?php _e('Your Stripe live publishable key (starts with pk_live_).', 'NORDBOOKING'); ?></p>
        <?php
    }
    
    public function live_secret_key_callback() {
        $settings = StripeConfig::get_settings();
        ?>
        <input type="password" name="<?php echo StripeConfig::OPTION_STRIPE_SETTINGS; ?>[live_secret_key]" value="<?php echo esc_attr($settings['live_secret_key']); ?>" class="regular-text" placeholder="sk_live_..." />
        <p class="description"><?php _e('Your Stripe live secret key (starts with sk_live_).', 'NORDBOOKING'); ?></p>
        <?php
    }
    
    public function live_webhook_secret_callback() {
        $settings = StripeConfig::get_settings();
        ?>
        <input type="password" name="<?php echo StripeConfig::OPTION_STRIPE_SETTINGS; ?>[live_webhook_secret]" value="<?php echo esc_attr($settings['live_webhook_secret']); ?>" class="regular-text" placeholder="whsec_..." />
        <p class="description"><?php _e('Your Stripe live webhook secret (starts with whsec_).', 'NORDBOOKING'); ?></p>
        <?php
    }
    
    public function price_id_callback() {
        $settings = StripeConfig::get_settings();
        ?>
        <input type="text" name="<?php echo StripeConfig::OPTION_STRIPE_SETTINGS; ?>[price_id]" value="<?php echo esc_attr($settings['price_id']); ?>" class="regular-text" placeholder="price_..." />
        <p class="description"><?php _e('Your Stripe price ID for the subscription (starts with price_).', 'NORDBOOKING'); ?></p>
        <?php
    }
}