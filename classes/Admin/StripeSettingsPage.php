<?php

namespace NORDBOOKING\Classes\Admin;

use NORDBOOKING\Classes\StripeConfig;

class StripeSettingsPage {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
    }
    
    public function add_admin_menu() {
        // Check if main NORDBOOKING menu exists, if not create it
        global $menu;
        $main_menu_exists = false;
        
        if (is_array($menu)) {
            foreach ($menu as $menu_item) {
                if (isset($menu_item[5]) && $menu_item[5] === 'toplevel_page_nordbooking-admin') {
                    $main_menu_exists = true;
                    break;
                }
            }
        }
        
        if (!$main_menu_exists) {
            // Create main NORDBOOKING admin menu
            add_menu_page(
                __('NORDBOOKING', 'NORDBOOKING'),
                __('NORDBOOKING', 'NORDBOOKING'),
                'manage_options',
                'nordbooking-admin',
                array($this, 'main_admin_page'),
                'dashicons-calendar-alt',
                30
            );
        }
        
        // Add Stripe Settings as submenu
        add_submenu_page(
            'nordbooking-admin',
            __('Stripe Settings', 'NORDBOOKING'),
            __('Stripe Settings', 'NORDBOOKING'),
            'manage_options',
            'nordbooking-stripe-settings',
            array($this, 'settings_page')
        );
    }
    
    public function main_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Nord Booking Administration', 'NORDBOOKING'); ?></h1>
            <p><?php _e('Welcome to Nord Booking administration. Use the menu items below to configure your booking system.', 'NORDBOOKING'); ?></p>
            
            <div class="nordbooking-admin-cards" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 30px;">
                <div class="admin-card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
                    <h3><?php _e('Stripe Integration', 'NORDBOOKING'); ?></h3>
                    <p><?php _e('Configure Stripe for subscription payments and billing management.', 'NORDBOOKING'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=nordbooking-stripe-settings'); ?>" class="button button-primary">
                        <?php _e('Stripe Settings', 'NORDBOOKING'); ?>
                    </a>
                </div>
                
                <div class="admin-card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
                    <h3><?php _e('Performance Monitoring', 'NORDBOOKING'); ?></h3>
                    <p><?php _e('Monitor system performance and optimize your booking system.', 'NORDBOOKING'); ?></p>
                    <a href="<?php echo admin_url('tools.php?page=nordbooking-performance'); ?>" class="button button-secondary">
                        <?php _e('Performance Dashboard', 'NORDBOOKING'); ?>
                    </a>
                </div>
                
                <div class="admin-card" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
                    <h3><?php _e('User Dashboard', 'NORDBOOKING'); ?></h3>
                    <p><?php _e('Access the main user dashboard and booking interface.', 'NORDBOOKING'); ?></p>
                    <a href="<?php echo home_url('/dashboard/'); ?>" class="button button-secondary">
                        <?php _e('Go to Dashboard', 'NORDBOOKING'); ?>
                    </a>
                </div>
            </div>
            
            <?php if (!\NORDBOOKING\Classes\StripeConfig::is_configured()): ?>
            <div class="notice notice-warning" style="margin-top: 30px;">
                <p><strong><?php _e('Setup Required:', 'NORDBOOKING'); ?></strong> 
                <?php _e('Stripe integration is not fully configured. ', 'NORDBOOKING'); ?>
                <a href="<?php echo admin_url('admin.php?page=nordbooking-stripe-settings'); ?>">
                    <?php _e('Configure Stripe Settings', 'NORDBOOKING'); ?>
                </a>
                </p>
            </div>
            <?php endif; ?>
        </div>
        <?php
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
        
        add_settings_field(
            'test_price_id',
            __('Test Price ID', 'NORDBOOKING'),
            array($this, 'test_price_id_callback'),
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
            'live_price_id',
            __('Live Price ID', 'NORDBOOKING'),
            array($this, 'live_price_id_callback'),
            'nordbooking_stripe_settings',
            'nordbooking_stripe_live'
        );
    }
    
    public function settings_page() {
        $settings = StripeConfig::get_settings();
        
        // Handle quick setup action
        if (isset($_POST['quick_setup']) && wp_verify_nonce($_POST['_wpnonce'], 'nordbooking_stripe_quick_setup')) {
            StripeConfig::initialize_test_settings();
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Test keys have been configured! You still need to set up a Price ID in Stripe Dashboard.', 'NORDBOOKING') . '</p></div>';
            $settings = StripeConfig::get_settings(); // Refresh settings
        }
        
        // Handle test connection action
        if (isset($_POST['test_connection']) && wp_verify_nonce($_POST['_wpnonce'], 'nordbooking_stripe_test')) {
            $test_result = StripeConfig::test_stripe_connection();
            $notice_class = $test_result['success'] ? 'notice-success' : 'notice-error';
            echo '<div class="notice ' . $notice_class . ' is-dismissible"><p>' . esc_html($test_result['message']) . '</p></div>';
        }
        
        // Handle database fix action
        if (isset($_POST['fix_database']) && wp_verify_nonce($_POST['_wpnonce'], 'nordbooking_fix_database')) {
            $fix_result = $this->fix_subscription_database();
            $notice_class = $fix_result['success'] ? 'notice-success' : 'notice-error';
            echo '<div class="notice ' . $notice_class . ' is-dismissible"><p>' . esc_html($fix_result['message']) . '</p></div>';
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php if (StripeConfig::is_configured()): ?>
                <div class="notice notice-success">
                    <p><?php _e('✅ Stripe is properly configured and ready to use!', 'NORDBOOKING'); ?></p>
                    <?php if (StripeConfig::is_test_mode()): ?>
                        <p><strong><?php _e('Currently in TEST MODE', 'NORDBOOKING'); ?></strong> - Use test cards for testing.</p>
                    <?php else: ?>
                        <p><strong><?php _e('Currently in LIVE MODE', 'NORDBOOKING'); ?></strong> - Real payments will be processed.</p>
                    <?php endif; ?>
                </div>
            <?php elseif (StripeConfig::needs_price_id()): ?>
                <div class="notice notice-warning">
                    <p><?php _e('⚠️ Stripe API keys are configured, but you need to set up a Price ID.', 'NORDBOOKING'); ?></p>
                    <p><?php _e('Create a product and price in your Stripe Dashboard, then add the Price ID below.', 'NORDBOOKING'); ?></p>
                </div>
            <?php elseif (StripeConfig::has_api_keys()): ?>
                <div class="notice notice-warning">
                    <p><?php _e('⚠️ Stripe API keys are configured, but missing Price ID.', 'NORDBOOKING'); ?></p>
                </div>
            <?php else: ?>
                <div class="notice notice-warning">
                    <p><?php _e('❌ Stripe configuration is incomplete. Please fill in all required fields.', 'NORDBOOKING'); ?></p>
                    <?php if (empty($settings['test_secret_key']) || empty($settings['test_publishable_key'])): ?>
                        <form method="post" style="margin-top: 10px;">
                            <?php wp_nonce_field('nordbooking_stripe_quick_setup'); ?>
                            <input type="hidden" name="quick_setup" value="1">
                            <button type="submit" class="button button-secondary">
                                <?php _e('🚀 Quick Setup with Test Keys', 'NORDBOOKING'); ?>
                            </button>
                            <p class="description"><?php _e('This will configure the test API keys and a sample price ID automatically for testing.', 'NORDBOOKING'); ?></p>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="notice notice-info">
                <p><strong><?php _e('Webhook URL:', 'NORDBOOKING'); ?></strong> <code><?php echo esc_url(home_url('/stripe-webhook/')); ?></code></p>
                <p><?php _e('Add this URL to your Stripe webhook endpoints in the Stripe Dashboard.', 'NORDBOOKING'); ?></p>
            </div>
            
            <?php if (StripeConfig::is_test_mode()): ?>
            <div class="notice notice-info">
                <h3><?php _e('Test Cards for Testing:', 'NORDBOOKING'); ?></h3>
                <ul>
                    <li><strong>Success:</strong> <code>4242 4242 4242 4242</code></li>
                    <li><strong>Declined:</strong> <code>4000 0000 0000 0002</code></li>
                    <li><strong>Requires Authentication:</strong> <code>4000 0025 0000 3155</code></li>
                </ul>
                <p><?php _e('Use any future expiry date and any 3-digit CVC.', 'NORDBOOKING'); ?></p>
            </div>
            <?php endif; ?>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('nordbooking_stripe_settings');
                do_settings_sections('nordbooking_stripe_settings');
                submit_button();
                ?>
            </form>
            
            <?php if (StripeConfig::is_configured()): ?>
            <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px;">
                <h3 style="margin-top: 0;"><?php _e('Test Connection', 'NORDBOOKING'); ?></h3>
                <p><?php _e('Test your Stripe configuration to make sure everything is working correctly.', 'NORDBOOKING'); ?></p>
                <form method="post" style="display: inline;">
                    <?php wp_nonce_field('nordbooking_stripe_test'); ?>
                    <input type="hidden" name="test_connection" value="1">
                    <button type="submit" class="button button-secondary">
                        <?php _e('🧪 Test Stripe Connection', 'NORDBOOKING'); ?>
                    </button>
                </form>
            </div>
            <?php endif; ?>
            
            <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px;">
                <h3 style="margin-top: 0;"><?php _e('Database Fix', 'NORDBOOKING'); ?></h3>
                <p><?php _e('If you\'re experiencing database errors with subscription creation, click the button below to fix database constraints.', 'NORDBOOKING'); ?></p>
                <form method="post" style="display: inline;">
                    <?php wp_nonce_field('nordbooking_fix_database'); ?>
                    <input type="hidden" name="fix_database" value="1">
                    <button type="submit" class="button button-warning">
                        <?php _e('🔧 Fix Database Constraints', 'NORDBOOKING'); ?>
                    </button>
                </form>
            </div>
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
    
    public function test_price_id_callback() {
        $settings = StripeConfig::get_settings();
        $validation_message = StripeConfig::get_price_id_validation_message($settings['test_price_id']);
        ?>
        <input type="text" name="<?php echo StripeConfig::OPTION_STRIPE_SETTINGS; ?>[test_price_id]" value="<?php echo esc_attr($settings['test_price_id']); ?>" class="regular-text" placeholder="price_..." />
        <p class="description"><?php _e('Your Stripe test price ID for the subscription (starts with price_).', 'NORDBOOKING'); ?></p>
        
        <?php if (!empty($validation_message)): ?>
        <div style="margin-top: 5px; padding: 8px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;">
            <p style="margin: 0; color: #856404;"><strong><?php _e('Validation:', 'NORDBOOKING'); ?></strong> <?php echo esc_html($validation_message); ?></p>
        </div>
        <?php endif; ?>
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
    
    public function live_price_id_callback() {
        $settings = StripeConfig::get_settings();
        $validation_message = StripeConfig::get_price_id_validation_message($settings['live_price_id']);
        ?>
        <input type="text" name="<?php echo StripeConfig::OPTION_STRIPE_SETTINGS; ?>[live_price_id]" value="<?php echo esc_attr($settings['live_price_id']); ?>" class="regular-text" placeholder="price_..." />
        <p class="description"><?php _e('Your Stripe live price ID for the subscription (starts with price_).', 'NORDBOOKING'); ?></p>
        
        <?php if (!empty($validation_message)): ?>
        <div style="margin-top: 5px; padding: 8px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;">
            <p style="margin: 0; color: #856404;"><strong><?php _e('Validation:', 'NORDBOOKING'); ?></strong> <?php echo esc_html($validation_message); ?></p>
        </div>
        <?php endif; ?>
        
        <?php if (empty($settings['live_price_id']) || !StripeConfig::is_valid_price_id($settings['live_price_id'])): ?>
        <div style="margin-top: 10px; padding: 10px; background: #f0f8ff; border-left: 4px solid #0073aa;">
            <h4 style="margin-top: 0;"><?php _e('How to get your Price ID:', 'NORDBOOKING'); ?></h4>
            <ol style="margin: 0;">
                <li><?php _e('Go to your <a href="https://dashboard.stripe.com/products" target="_blank">Stripe Dashboard → Products</a>', 'NORDBOOKING'); ?></li>
                <li><?php _e('Find your product or create a new one', 'NORDBOOKING'); ?></li>
                <li><?php _e('Click on the product to view its prices', 'NORDBOOKING'); ?></li>
                <li><?php _e('Find the recurring price you want to use', 'NORDBOOKING'); ?></li>
                <li><?php _e('Copy the <strong>Price ID</strong> (starts with "price_") - NOT the Product ID', 'NORDBOOKING'); ?></li>
                <li><?php _e('Paste the Price ID in the field above', 'NORDBOOKING'); ?></li>
            </ol>
            <?php if (strpos($settings['live_price_id'], 'prod_') === 0): ?>
            <div style="margin-top: 10px; padding: 8px; background: #fff3cd; border: 1px solid #ffeaa7;">
                <p style="margin: 0;"><strong><?php _e('Important:', 'NORDBOOKING'); ?></strong> <?php _e('This appears to be a Product ID, not a Price ID. In Stripe, a Product can have multiple Prices (e.g., monthly, yearly). Make sure to copy the Price ID, not the Product ID.', 'NORDBOOKING'); ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div style="margin-top: 10px; padding: 8px; background: #d4edda; border: 1px solid #c3e6cb;">
            <p style="margin: 0;"><strong><?php _e('✅ Configured:', 'NORDBOOKING'); ?></strong> <?php _e('Price ID is properly configured. The subscription system is ready!', 'NORDBOOKING'); ?></p>
        </div>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Fix subscription database constraints
     */
    public function fix_subscription_database() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'nordbooking_subscriptions';
        
        try {
            // Check if table exists
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
            
            if (!$table_exists) {
                // Install the table
                \NORDBOOKING\Classes\Subscription::install();
                return [
                    'success' => true,
                    'message' => 'Subscription table created successfully.'
                ];
            }
            
            // Fix constraints
            $messages = [];
            
            // Drop the problematic unique constraint
            $result = $wpdb->query("ALTER TABLE $table_name DROP INDEX IF EXISTS stripe_subscription_id_unique");
            if ($result !== false) {
                $messages[] = 'Removed problematic unique constraint on stripe_subscription_id.';
            }
            
            // Clean up empty string values
            $result = $wpdb->query("UPDATE $table_name SET stripe_subscription_id = NULL WHERE stripe_subscription_id = ''");
            if ($result !== false) {
                $messages[] = 'Cleaned up empty string values in stripe_subscription_id.';
            }
            
            // Show current table structure
            $structure = $wpdb->get_results("DESCRIBE $table_name");
            $messages[] = 'Current table structure updated.';
            
            return [
                'success' => true,
                'message' => implode(' ', $messages)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Database fix failed: ' . $e->getMessage()
            ];
        }
    }
}