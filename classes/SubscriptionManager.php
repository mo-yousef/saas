<?php

namespace NORDBOOKING\Classes;

/**
 * Enhanced Subscription Manager with Real-time Sync
 * Provides comprehensive subscription management with automatic sync
 */
class SubscriptionManager {
    
    private static $instance = null;
    private $cache_duration = 300; // 5 minutes
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Hook into WordPress actions
        add_action('wp_ajax_nordbooking_real_time_sync', [$this, 'handle_real_time_sync']);
        add_action('wp_ajax_nordbooking_subscription_status_check', [$this, 'handle_status_check']);
        add_action('wp_ajax_nordbooking_run_subscription_test', [$this, 'handle_run_test']);
        
        // Schedule regular sync checks
        add_action('nordbooking_subscription_sync_check', [$this, 'scheduled_sync_check']);
        
        // Hook into user login to sync subscription
        add_action('wp_login', [$this, 'sync_on_login'], 10, 2);
    }
    
    /**
     * Get subscription with real-time sync
     */
    public function get_subscription_with_sync($user_id, $force_sync = false) {
        $cache_key = "nordbooking_subscription_{$user_id}";
        
        // Check cache first (unless force sync)
        if (!$force_sync) {
            $cached = get_transient($cache_key);
            if ($cached !== false) {
                return $cached;
            }
        }
        
        // Get subscription from database
        $subscription = \NORDBOOKING\Classes\Subscription::get_subscription($user_id);
        
        // If we have a Stripe subscription ID, sync with Stripe
        if ($subscription && !empty($subscription['stripe_subscription_id']) && \NORDBOOKING\Classes\StripeConfig::is_configured()) {
            $this->sync_with_stripe($user_id, $subscription['stripe_subscription_id']);
            // Get updated subscription
            $subscription = \NORDBOOKING\Classes\Subscription::get_subscription($user_id);
        }
        
        // Cache the result
        set_transient($cache_key, $subscription, $this->cache_duration);
        
        return $subscription;
    }
    
    /**
     * Get subscription status with real-time validation
     */
    public function get_status_with_validation($user_id, $force_sync = false) {
        $subscription = $this->get_subscription_with_sync($user_id, $force_sync);
        
        if (!$subscription) {
            return 'unsubscribed';
        }
        
        // Validate status based on dates
        $now = new \DateTime();
        
        // Check trial expiration
        if ($subscription['status'] === 'trial' && !empty($subscription['trial_ends_at'])) {
            $trial_ends = new \DateTime($subscription['trial_ends_at']);
            if ($now > $trial_ends) {
                $this->update_subscription_status($user_id, 'expired_trial');
                return 'expired_trial';
            }
        }
        
        // Check subscription expiration
        if (in_array($subscription['status'], ['active', 'cancelled']) && !empty($subscription['ends_at'])) {
            $ends_at = new \DateTime($subscription['ends_at']);
            $grace_period = clone $ends_at;
            $grace_period->modify('+2 days');
            
            if ($now > $grace_period) {
                $this->update_subscription_status($user_id, 'expired');
                return 'expired';
            }
            
            // If cancelled but still within period, show as active
            if ($subscription['status'] === 'cancelled' && $now <= $ends_at) {
                return 'active';
            }
        }
        
        return $subscription['status'];
    }
    
    /**
     * Sync with Stripe and update local data
     */
    private function sync_with_stripe($user_id, $stripe_subscription_id) {
        try {
            \Stripe\Stripe::setApiKey(\NORDBOOKING\Classes\StripeConfig::get_secret_key());
            $stripe_subscription = \Stripe\Subscription::retrieve($stripe_subscription_id);
            
            // Update local subscription
            $this->update_from_stripe_subscription($user_id, $stripe_subscription);
            
            // Clear cache
            delete_transient("nordbooking_subscription_{$user_id}");
            
            return true;
        } catch (\Exception $e) {
            error_log('Stripe sync failed for user ' . $user_id . ': ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update local subscription from Stripe data
     */
    private function update_from_stripe_subscription($user_id, $stripe_subscription) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'nordbooking_subscriptions';
        
        $status = $this->map_stripe_status($stripe_subscription->status);
        $trial_ends_at = null;
        
        if ($stripe_subscription->trial_end) {
            $trial_ends_at = date('Y-m-d H:i:s', $stripe_subscription->trial_end);
        }
        
        $update_data = [
            'status' => $status,
            'stripe_subscription_id' => $stripe_subscription->id,
            'ends_at' => date('Y-m-d H:i:s', $stripe_subscription->current_period_end),
            'trial_ends_at' => $trial_ends_at,
            'updated_at' => current_time('mysql')
        ];
        
        $wpdb->update(
            $table_name,
            $update_data,
            ['user_id' => $user_id]
        );
    }
    
    /**
     * Map Stripe status to local status
     */
    private function map_stripe_status($stripe_status) {
        $status_map = [
            'active' => 'active',
            'canceled' => 'cancelled',
            'past_due' => 'past_due',
            'unpaid' => 'unpaid',
            'trialing' => 'trial',
            'incomplete' => 'pending',
            'incomplete_expired' => 'expired'
        ];
        
        return $status_map[$stripe_status] ?? 'active';
    }
    
    /**
     * Update subscription status
     */
    private function update_subscription_status($user_id, $status) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'nordbooking_subscriptions';
        
        $wpdb->update(
            $table_name,
            [
                'status' => $status,
                'updated_at' => current_time('mysql')
            ],
            ['user_id' => $user_id]
        );
        
        // Clear cache
        delete_transient("nordbooking_subscription_{$user_id}");
    }
    
    /**
     * Handle real-time sync AJAX request
     */
    public function handle_real_time_sync() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'nordbooking_dashboard_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => 'User not logged in']);
            return;
        }
        
        // Force sync
        $subscription = $this->get_subscription_with_sync($user_id, true);
        $status = $this->get_status_with_validation($user_id, true);
        
        wp_send_json_success([
            'status' => $status,
            'subscription' => $subscription,
            'synced_at' => current_time('mysql')
        ]);
    }
    
    /**
     * Handle status check AJAX request
     */
    public function handle_status_check() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'nordbooking_dashboard_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => 'User not logged in']);
            return;
        }
        
        $status = $this->get_status_with_validation($user_id);
        $subscription = $this->get_subscription_with_sync($user_id);
        $days_left = \NORDBOOKING\Classes\Subscription::get_days_until_next_payment($user_id);
        
        wp_send_json_success([
            'status' => $status,
            'days_left' => $days_left,
            'subscription' => $subscription,
            'checked_at' => current_time('mysql')
        ]);
    }
    
    /**
     * Handle test runner AJAX request
     */
    public function handle_run_test() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'nordbooking_admin_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }
        
        $test_user_id = intval($_POST['test_user_id'] ?? 0);
        if (!$test_user_id) {
            $test_user_id = get_current_user_id();
        }
        
        $tester = new SubscriptionTester();
        $results = $tester->run_complete_test($test_user_id);
        
        wp_send_json_success([
            'results' => $results,
            'html_report' => $tester->generate_html_report()
        ]);
    }
    
    /**
     * Scheduled sync check for all active subscriptions
     */
    public function scheduled_sync_check() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'nordbooking_subscriptions';
        
        // Get subscriptions that need sync (updated more than 1 hour ago)
        $subscriptions = $wpdb->get_results(
            "SELECT user_id, stripe_subscription_id 
             FROM $table_name 
             WHERE stripe_subscription_id IS NOT NULL 
             AND status IN ('active', 'trial', 'past_due') 
             AND updated_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)
             LIMIT 50"
        );
        
        foreach ($subscriptions as $subscription) {
            $this->sync_with_stripe($subscription->user_id, $subscription->stripe_subscription_id);
            
            // Small delay to avoid rate limiting
            usleep(100000); // 0.1 seconds
        }
    }
    
    /**
     * Sync subscription on user login
     */
    public function sync_on_login($user_login, $user) {
        if (!$user || !$user->ID) {
            return;
        }
        
        // Schedule sync for next request to avoid slowing down login
        wp_schedule_single_event(time() + 30, 'nordbooking_user_login_sync', [$user->ID]);
    }
    
    /**
     * Get subscription analytics
     */
    public function get_subscription_analytics() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'nordbooking_subscriptions';
        
        $analytics = [
            'total_subscriptions' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name"),
            'active_subscriptions' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'active'"),
            'trial_subscriptions' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'trial'"),
            'expired_subscriptions' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status IN ('expired', 'expired_trial')"),
            'cancelled_subscriptions' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'cancelled'"),
            'conversion_rate' => 0,
            'churn_rate' => 0,
            'mrr' => 0
        ];
        
        // Calculate conversion rate (trial to active)
        if ($analytics['trial_subscriptions'] > 0) {
            $total_trials = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status IN ('trial', 'active', 'expired_trial')");
            if ($total_trials > 0) {
                $analytics['conversion_rate'] = round(($analytics['active_subscriptions'] / $total_trials) * 100, 2);
            }
        }
        
        // Calculate churn rate
        $total_ever_active = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status IN ('active', 'cancelled', 'expired')");
        if ($total_ever_active > 0) {
            $churned = $analytics['cancelled_subscriptions'] + $analytics['expired_subscriptions'];
            $analytics['churn_rate'] = round(($churned / $total_ever_active) * 100, 2);
        }
        
        // Calculate MRR (Monthly Recurring Revenue)
        if (\NORDBOOKING\Classes\StripeConfig::is_configured()) {
            $pricing = \NORDBOOKING\Classes\Subscription::get_pricing_info();
            if ($pricing && $pricing['interval'] === 'month') {
                $analytics['mrr'] = ($analytics['active_subscriptions'] * $pricing['amount']) / 100;
            }
        }
        
        return $analytics;
    }
    
    /**
     * Get subscription health status
     */
    public function get_health_status() {
        $analytics = $this->get_subscription_analytics();
        $config_status = \NORDBOOKING\Classes\StripeConfig::get_configuration_status();
        
        $health = [
            'overall_status' => 'healthy',
            'issues' => [],
            'warnings' => [],
            'recommendations' => []
        ];
        
        // Check configuration
        if ($config_status['status'] !== 'complete') {
            $health['overall_status'] = 'critical';
            $health['issues'][] = $config_status['message'];
        }
        
        // Check conversion rate
        if ($analytics['conversion_rate'] < 20) {
            $health['warnings'][] = "Low conversion rate ({$analytics['conversion_rate']}%). Consider improving onboarding.";
        }
        
        // Check churn rate
        if ($analytics['churn_rate'] > 10) {
            $health['warnings'][] = "High churn rate ({$analytics['churn_rate']}%). Review customer satisfaction.";
        }
        
        // Check for stale data
        global $wpdb;
        $table_name = $wpdb->prefix . 'nordbooking_subscriptions';
        $stale_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_name 
             WHERE stripe_subscription_id IS NOT NULL 
             AND updated_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );
        
        if ($stale_count > 0) {
            $health['warnings'][] = "$stale_count subscriptions haven't been synced in 24+ hours.";
        }
        
        // Set overall status based on issues
        if (!empty($health['issues'])) {
            $health['overall_status'] = 'critical';
        } elseif (!empty($health['warnings'])) {
            $health['overall_status'] = 'warning';
        }
        
        return $health;
    }
}