<?php
/**
 * Test Company Name Validation
 * 
 * This file tests the company name validation functionality to ensure:
 * 1. Duplicate company names are properly detected and rejected
 * 2. Company names are automatically saved to settings during registration
 * 3. Company names are displayed consistently across the system
 * 
 * @package NORDBOOKING
 */

// Include WordPress
require_once('wp-config.php');

if (!defined('ABSPATH')) {
    die('WordPress not loaded properly');
}

// Test class
class CompanyNameValidationTest {
    
    private $auth_instance;
    private $settings_manager;
    
    public function __construct() {
        $this->auth_instance = new \NORDBOOKING\Classes\Auth();
        $this->settings_manager = new \NORDBOOKING\Classes\Settings();
    }
    
    public function run_tests() {
        echo "<h1>Company Name Validation Tests</h1>\n";
        
        $this->test_company_name_uniqueness();
        $this->test_company_name_in_settings();
        $this->test_company_name_display();
        
        echo "<h2>All tests completed!</h2>\n";
    }
    
    private function test_company_name_uniqueness() {
        echo "<h2>Test 1: Company Name Uniqueness</h2>\n";
        
        // Create a test user with a company name
        $test_company_name = 'Test Company ' . time();
        
        $user_id = wp_create_user(
            'test_' . time() . '@example.com',
            'password123',
            'test_' . time() . '@example.com'
        );
        
        if (is_wp_error($user_id)) {
            echo "<p style='color: red;'>Failed to create test user: " . $user_id->get_error_message() . "</p>\n";
            return;
        }
        
        // Save company name
        update_user_meta($user_id, 'nordbooking_company_name', $test_company_name);
        $this->settings_manager->update_setting($user_id, 'biz_name', $test_company_name);
        
        echo "<p>Created test user with company name: <strong>$test_company_name</strong></p>\n";
        
        // Test the validation method
        $reflection = new ReflectionClass($this->auth_instance);
        $method = $reflection->getMethod('get_user_id_by_company_name');
        $method->setAccessible(true);
        
        $found_user_id = $method->invoke($this->auth_instance, $test_company_name);
        
        if ($found_user_id === $user_id) {
            echo "<p style='color: green;'>✓ Company name uniqueness validation working correctly</p>\n";
        } else {
            echo "<p style='color: red;'>✗ Company name uniqueness validation failed</p>\n";
        }
        
        // Test with different case
        $found_user_id_case = $method->invoke($this->auth_instance, strtoupper($test_company_name));
        if ($found_user_id_case === null) {
            echo "<p style='color: green;'>✓ Company name validation is case-sensitive (as expected)</p>\n";
        } else {
            echo "<p style='color: orange;'>! Company name validation found case-insensitive match</p>\n";
        }
        
        // Clean up
        wp_delete_user($user_id);
        echo "<p>Test user cleaned up</p>\n";
    }
    
    private function test_company_name_in_settings() {
        echo "<h2>Test 2: Company Name in Settings</h2>\n";
        
        $test_company_name = 'Settings Test Company ' . time();
        
        $user_id = wp_create_user(
            'settings_test_' . time() . '@example.com',
            'password123',
            'settings_test_' . time() . '@example.com'
        );
        
        if (is_wp_error($user_id)) {
            echo "<p style='color: red;'>Failed to create test user: " . $user_id->get_error_message() . "</p>\n";
            return;
        }
        
        // Simulate the registration process
        update_user_meta($user_id, 'nordbooking_company_name', $test_company_name);
        $this->settings_manager->update_setting($user_id, 'biz_name', $test_company_name);
        
        // Check if company name is in settings
        $biz_name_setting = $this->settings_manager->get_setting($user_id, 'biz_name', '');
        
        if ($biz_name_setting === $test_company_name) {
            echo "<p style='color: green;'>✓ Company name correctly saved to business settings</p>\n";
        } else {
            echo "<p style='color: red;'>✗ Company name not found in business settings. Found: '$biz_name_setting'</p>\n";
        }
        
        // Test default settings initialization
        \NORDBOOKING\Classes\Settings::initialize_default_settings($user_id);
        
        $biz_name_after_init = $this->settings_manager->get_setting($user_id, 'biz_name', '');
        if ($biz_name_after_init === $test_company_name) {
            echo "<p style='color: green;'>✓ Company name preserved after default settings initialization</p>\n";
        } else {
            echo "<p style='color: red;'>✗ Company name lost after default settings initialization</p>\n";
        }
        
        // Clean up
        wp_delete_user($user_id);
        echo "<p>Test user cleaned up</p>\n";
    }
    
    private function test_company_name_display() {
        echo "<h2>Test 3: Company Name Display</h2>\n";
        
        $test_company_name = 'Display Test Company ' . time();
        
        $user_id = wp_create_user(
            'display_test_' . time() . '@example.com',
            'password123',
            'display_test_' . time() . '@example.com'
        );
        
        if (is_wp_error($user_id)) {
            echo "<p style='color: red;'>Failed to create test user: " . $user_id->get_error_message() . "</p>\n";
            return;
        }
        
        // Set up company name
        update_user_meta($user_id, 'nordbooking_company_name', $test_company_name);
        $this->settings_manager->update_setting($user_id, 'biz_name', $test_company_name);
        
        // Test business name retrieval for notifications
        $notifications = new \NORDBOOKING\Classes\Notifications();
        $reflection = new ReflectionClass($notifications);
        $method = $reflection->getMethod('get_business_name_for_user');
        $method->setAccessible(true);
        
        $business_name = $method->invoke($notifications, $user_id);
        
        if ($business_name === $test_company_name) {
            echo "<p style='color: green;'>✓ Business name correctly retrieved for notifications</p>\n";
        } else {
            echo "<p style='color: red;'>✗ Business name not correctly retrieved. Got: '$business_name'</p>\n";
        }
        
        // Test business settings retrieval
        $business_settings = $this->settings_manager->get_business_settings($user_id);
        if (isset($business_settings['biz_name']) && $business_settings['biz_name'] === $test_company_name) {
            echo "<p style='color: green;'>✓ Company name correctly included in business settings</p>\n";
        } else {
            echo "<p style='color: red;'>✗ Company name not found in business settings array</p>\n";
        }
        
        // Clean up
        wp_delete_user($user_id);
        echo "<p>Test user cleaned up</p>\n";
    }
}

// Run tests if accessed directly
if (isset($_GET['run_tests']) && $_GET['run_tests'] === '1') {
    $test = new CompanyNameValidationTest();
    $test->run_tests();
} else {
    echo "<h1>Company Name Validation Test</h1>";
    echo "<p><a href='?run_tests=1'>Click here to run tests</a></p>";
    echo "<p><strong>Note:</strong> This will create and delete test users. Only run on development environments.</p>";
}
?>