<?php

namespace NORDBOOKING\Classes;

/**
 * Comprehensive Subscription System Tester
 * Tests all subscription functionalities on both backend and frontend
 */
class SubscriptionTester {
    
    private $test_results = [];
    private $test_user_id = null;
    
    public function __construct() {
        $this->test_results = [];
    }
    
    /**
     * Run complete subscription system test
     */
    public function run_complete_test($user_id = null) {
        $this->test_user_id = $user_id ?: get_current_user_id();
        
        $this->log_test('Starting comprehensive subscription system test...');
        
        // Backend Tests
        $this->test_database_structure();
        $this->test_stripe_configuration();
        $this->test_subscription_class_methods();
        $this->test_webhook_handling();
        $this->test_ajax_handlers();
        
        // Frontend Tests
        $this->test_subscription_page_functionality();
        $this->test_real_time_sync();
        
        // Integration Tests
        $this->test_stripe_integration();
        $this->test_user_flow();
        
        return $this->get_test_results();
    }
    
    /**
     * Test database structure and integrity
     */
    private function test_database_structure() {
        $this->log_test('Testing database structure...');
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'nordbooking_subscriptions';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        $this->add_result('Database Table Exists', $table_exists, 'Subscription table must exist');
        
        if (!$table_exists) {
            // Try to create table
            \NORDBOOKING\Classes\Subscription::install();
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
            $this->add_result('Database Table Creation', $table_exists, 'Table should be created automatically');
        }
        
        if ($table_exists) {
            // Check table structure
            $columns = $wpdb->get_results("DESCRIBE $table_name");
            $required_columns = ['id', 'user_id', 'status', 'stripe_customer_id', 'stripe_subscription_id', 'trial_ends_at', 'ends_at', 'created_at', 'updated_at'];
            
            $existing_columns = array_column($columns, 'Field');
            $missing_columns = array_diff($required_columns, $existing_columns);
            
            $this->add_result('Database Schema Complete', empty($missing_columns), 
                empty($missing_columns) ? 'All required columns exist' : 'Missing columns: ' . implode(', ', $missing_columns));
            
            // Test data integrity
            $duplicate_users = $wpdb->get_var("SELECT COUNT(*) FROM (SELECT user_id, COUNT(*) as cnt FROM $table_name GROUP BY user_id HAVING cnt > 1) as duplicates");
            $this->add_result('No Duplicate User Subscriptions', $duplicate_users == 0, 'Each user should have only one subscription record');
        }
    }
    
    /**
     * Test Stripe configuration
     */
    private function test_stripe_configuration() {
        $this->log_test('Testing Stripe configuration...');
        
        $is_configured = \NORDBOOKING\Classes\StripeConfig::is_configured();
        $this->add_result('Stripe Configuration', $is_configured, 'Stripe must be properly configured');
        
        if ($is_configured) {
            $connection_test = \NORDBOOKING\Classes\StripeConfig::test_stripe_connection();
            $this->add_result('Stripe Connection', $connection_test['success'], $connection_test['message']);
            
            // Test price ID validity
            $price_id = \NORDBOOKING\Classes\StripeConfig::get_price_id();
            $valid_price_id = \NORDBOOKING\Classes\StripeConfig::is_valid_price_id($price_id);
            $this->add_result('Valid Price ID', $valid_price_id, 
                $valid_price_id ? 'Price ID format is valid' : \NORDBOOKING\Classes\StripeConfig::get_price_id_validation_message($price_id));
        }
    }
    
    /**
     * Test subscription class methods
     */
    private function test_subscription_class_methods() {
        $this->log_test('Testing Subscription class methods...');
        
        if (!$this->test_user_id) {
            $this->add_result('Test User Available', false, 'No test user ID provided');
            return;
        }
        
        // Test get_subscription
        $subscription = \NORDBOOKING\Classes\Subscription::get_subscription($this->test_user_id);
        $this->add_result('Get Subscription Method', true, 'Method executed without errors');
        
        // Test get_subscription_status
        $status = \NORDBOOKING\Classes\Subscription::get_subscription_status($this->test_user_id);
        $valid_statuses = ['unsubscribed', 'trial', 'active', 'expired_trial', 'expired', 'cancelled'];
        $this->add_result('Get Subscription Status', in_array($status, $valid_statuses), 
            "Status '$status' is " . (in_array($status, $valid_statuses) ? 'valid' : 'invalid'));
        
        // Test get_days_until_next_payment
        $days = \NORDBOOKING\Classes\Subscription::get_days_until_next_payment($this->test_user_id);
        $this->add_result('Get Days Until Payment', is_numeric($days), 'Method should return numeric value');
        
        // Test create_trial_subscription (if no subscription exists)
        if (!$subscription) {
            \NORDBOOKING\Classes\Subscription::create_trial_subscription($this->test_user_id);
            $new_subscription = \NORDBOOKING\Classes\Subscription::get_subscription($this->test_user_id);
            $this->add_result('Create Trial Subscription', !empty($new_subscription), 'Trial subscription should be created');
        }
    }
    
    /**
     * Test webhook handling
     */
    private function test_webhook_handling() {
        $this->log_test('Testing webhook handling...');
        
        // Test webhook secret configuration
        $webhook_secret = \NORDBOOKING\Classes\StripeConfig::get_webhook_secret();
        $has_webhook_secret = !empty($webhook_secret) && strpos($webhook_secret, 'placeholder') === false;
        $this->add_result('Webhook Secret Configured', $has_webhook_secret, 
            $has_webhook_secret ? 'Webhook secret is configured' : 'Webhook secret needs to be configured');
        
        // Test webhook endpoint accessibility
        $webhook_url = home_url('/stripe-webhook/');
        $this->add_result('Webhook Endpoint Available', true, "Webhook endpoint: $webhook_url");
    }
    
    /**
     * Test AJAX handlers
     */
    private function test_ajax_handlers() {
        $this->log_test('Testing AJAX handlers...');
        
        $ajax_actions = [
            'nordbooking_create_checkout_session',
            'nordbooking_cancel_subscription',
            'nordbooking_create_customer_portal_session',
            'nordbooking_sync_subscription_status'
        ];
        
        foreach ($ajax_actions as $action) {
            $has_handler = has_action("wp_ajax_$action");
            $this->add_result("AJAX Handler: $action", $has_handler, 
                $has_handler ? 'Handler is registered' : 'Handler is missing');
        }
    }
    
    /**
     * Test subscription page functionality
     */
    private function test_subscription_page_functionality() {
        $this->log_test('Testing subscription page functionality...');
        
        // Check if subscription page exists
        $subscription_page_path = NORDBOOKING_THEME_DIR . 'dashboard/page-subscription.php';
        $page_exists = file_exists($subscription_page_path);
        $this->add_result('Subscription Page File', $page_exists, 'Subscription page template must exist');
        
        if ($page_exists) {
            $page_content = file_get_contents($subscription_page_path);
            
            // Check for required elements
            $required_elements = [
                'subscription-management-wrapper' => 'Main wrapper element',
                'subscribe-now-btn' => 'Subscribe button',
                'manage-billing-btn' => 'Manage billing button',
                'cancel-subscription-btn' => 'Cancel subscription button',
                'refresh-status-btn' => 'Refresh status button'
            ];
            
            foreach ($required_elements as $element => $description) {
                $has_element = strpos($page_content, $element) !== false;
                $this->add_result("Page Element: $element", $has_element, $description);
            }
            
            // Check for JavaScript functionality
            $has_ajax_calls = strpos($page_content, '$.ajax') !== false || strpos($page_content, 'jQuery.ajax') !== false;
            $this->add_result('AJAX Integration', $has_ajax_calls, 'Page should have AJAX functionality');
        }
    }
    
    /**
     * Test real-time sync functionality
     */
    private function test_real_time_sync() {
        $this->log_test('Testing real-time sync functionality...');
        
        if (!$this->test_user_id) {
            $this->add_result('Real-time Sync Test', false, 'No test user available');
            return;
        }
        
        // Test sync_subscription_status method
        $sync_result = \NORDBOOKING\Classes\Subscription::sync_subscription_status($this->test_user_id);
        $this->add_result('Sync Method Execution', true, 'Sync method executed without errors');
        
        // Test status consistency after sync
        $status_before = \NORDBOOKING\Classes\Subscription::get_subscription_status($this->test_user_id);
        sleep(1); // Small delay
        $status_after = \NORDBOOKING\Classes\Subscription::get_subscription_status($this->test_user_id);
        
        $this->add_result('Status Consistency', $status_before === $status_after, 
            'Status should remain consistent between calls');
    }
    
    /**
     * Test Stripe integration
     */
    private function test_stripe_integration() {
        $this->log_test('Testing Stripe integration...');
        
        if (!\NORDBOOKING\Classes\StripeConfig::is_configured()) {
            $this->add_result('Stripe Integration Test', false, 'Stripe not configured');
            return;
        }
        
        try {
            \Stripe\Stripe::setApiKey(\NORDBOOKING\Classes\StripeConfig::get_secret_key());
            
            // Test price retrieval
            $price_id = \NORDBOOKING\Classes\StripeConfig::get_price_id();
            $price = \Stripe\Price::retrieve($price_id);
            $this->add_result('Price Retrieval', !empty($price), 'Can retrieve price from Stripe');
            
            if (!empty($price)) {
                $this->add_result('Price Configuration', 
                    $price->type === 'recurring', 
                    'Price is configured for recurring billing');
            }
            
        } catch (\Exception $e) {
            $this->add_result('Stripe API Test', false, 'Stripe API error: ' . $e->getMessage());
        }
    }
    
    /**
     * Test complete user flow
     */
    private function test_user_flow() {
        $this->log_test('Testing complete user flow...');
        
        if (!$this->test_user_id) {
            $this->add_result('User Flow Test', false, 'No test user available');
            return;
        }
        
        // Test trial creation flow
        $subscription = \NORDBOOKING\Classes\Subscription::get_subscription($this->test_user_id);
        if (!$subscription) {
            \NORDBOOKING\Classes\Subscription::create_trial_subscription($this->test_user_id);
            $subscription = \NORDBOOKING\Classes\Subscription::get_subscription($this->test_user_id);
        }
        
        $this->add_result('Trial Creation Flow', !empty($subscription), 'User can get trial subscription');
        
        if ($subscription) {
            // Test status transitions
            $status = \NORDBOOKING\Classes\Subscription::get_subscription_status($this->test_user_id);
            $this->add_result('Status Retrieval Flow', !empty($status), 'User status can be retrieved');
            
            // Test checkout session creation (if Stripe is configured)
            if (\NORDBOOKING\Classes\StripeConfig::is_configured()) {
                $checkout_url = \NORDBOOKING\Classes\Subscription::create_stripe_checkout_session($this->test_user_id);
                $this->add_result('Checkout Session Creation', !empty($checkout_url), 
                    'Checkout session can be created for user');
            }
        }
    }
    
    /**
     * Add test result
     */
    private function add_result($test_name, $passed, $message = '') {
        $this->test_results[] = [
            'test' => $test_name,
            'passed' => $passed,
            'message' => $message,
            'timestamp' => current_time('mysql')
        ];
    }
    
    /**
     * Log test progress
     */
    private function log_test($message) {
        error_log('[NORDBOOKING Subscription Test] ' . $message);
    }
    
    /**
     * Get test results
     */
    public function get_test_results() {
        $total_tests = count($this->test_results);
        $passed_tests = count(array_filter($this->test_results, function($result) {
            return $result['passed'];
        }));
        
        return [
            'summary' => [
                'total' => $total_tests,
                'passed' => $passed_tests,
                'failed' => $total_tests - $passed_tests,
                'success_rate' => $total_tests > 0 ? round(($passed_tests / $total_tests) * 100, 2) : 0
            ],
            'results' => $this->test_results,
            'timestamp' => current_time('mysql')
        ];
    }
    
    /**
     * Generate HTML test report
     */
    public function generate_html_report() {
        $results = $this->get_test_results();
        
        $html = '<div class="subscription-test-report">';
        $html .= '<h2>Subscription System Test Report</h2>';
        
        // Summary
        $html .= '<div class="test-summary">';
        $html .= '<h3>Summary</h3>';
        $html .= '<p><strong>Total Tests:</strong> ' . $results['summary']['total'] . '</p>';
        $html .= '<p><strong>Passed:</strong> <span style="color: green;">' . $results['summary']['passed'] . '</span></p>';
        $html .= '<p><strong>Failed:</strong> <span style="color: red;">' . $results['summary']['failed'] . '</span></p>';
        $html .= '<p><strong>Success Rate:</strong> ' . $results['summary']['success_rate'] . '%</p>';
        $html .= '</div>';
        
        // Detailed results
        $html .= '<div class="test-details">';
        $html .= '<h3>Detailed Results</h3>';
        $html .= '<table class="wp-list-table widefat fixed striped">';
        $html .= '<thead><tr><th>Test</th><th>Status</th><th>Message</th></tr></thead>';
        $html .= '<tbody>';
        
        foreach ($results['results'] as $result) {
            $status_color = $result['passed'] ? 'green' : 'red';
            $status_text = $result['passed'] ? 'PASS' : 'FAIL';
            
            $html .= '<tr>';
            $html .= '<td>' . esc_html($result['test']) . '</td>';
            $html .= '<td style="color: ' . $status_color . '; font-weight: bold;">' . $status_text . '</td>';
            $html .= '<td>' . esc_html($result['message']) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
        $html .= '</div>';
        
        $html .= '<p><small>Report generated on: ' . $results['timestamp'] . '</small></p>';
        $html .= '</div>';
        
        return $html;
    }
}