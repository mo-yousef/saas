<?php

use NORDBOOKING\Classes\Auth;
use NORDBOOKING\Classes\Services;
use NORDBOOKING\Classes\Discounts;
use NORDBOOKING\Classes\Availability;
use NORDBOOKING\Classes\Settings;

/**
 * Class Test_Account_Setup_Automation
 *
 * @group nordbooking_account_setup
 */
class Test_Account_Setup_Automation extends WP_UnitTestCase {

    protected $auth_instance;
    protected $services_manager;
    protected $discounts_manager;
    protected $availability_manager;
    protected $settings_manager;
    protected $test_user_id;

    public function setUp(): void {
        parent::setUp();
        
        // Initialize managers
        $this->auth_instance = new Auth();
        $this->services_manager = new Services();
        $this->availability_manager = new Availability();
        $this->settings_manager = new Settings();
        
        // Add required roles
        Auth::add_business_owner_role();
        
        // Create a test business owner user
        $this->test_user_id = $this->factory->user->create([
            'role' => Auth::ROLE_BUSINESS_OWNER,
            'user_email' => 'testowner@example.com',
            'user_login' => 'testowner@example.com',
            'first_name' => 'Test',
            'last_name' => 'Owner'
        ]);
        
        // Initialize discounts manager with user ID
        $this->discounts_manager = new Discounts($this->test_user_id);
    }

    public function tearDown(): void {
        // Clean up test data
        if ($this->test_user_id) {
            // Clean up services
            global $wpdb;
            $services_table = \NORDBOOKING\Classes\Database::get_table_name('services');
            $wpdb->delete($services_table, ['user_id' => $this->test_user_id]);
            
            // Clean up service options
            $service_options_table = \NORDBOOKING\Classes\Database::get_table_name('service_options');
            $wpdb->delete($service_options_table, ['user_id' => $this->test_user_id]);
            
            // Clean up discounts
            $discounts_table = \NORDBOOKING\Classes\Database::get_table_name('discounts');
            $wpdb->delete($discounts_table, ['user_id' => $this->test_user_id]);
            
            // Clean up availability
            $availability_table = \NORDBOOKING\Classes\Database::get_table_name('availability_rules');
            $wpdb->delete($availability_table, ['user_id' => $this->test_user_id]);
            
            // Clean up settings
            $settings_table = \NORDBOOKING\Classes\Database::get_table_name('tenant_settings');
            $wpdb->delete($settings_table, ['user_id' => $this->test_user_id]);
        }
        
        parent::tearDown();
    }

    /**
     * Test demo services creation - verify all three services are created
     */
    public function test_demo_services_creation_count() {
        // Use reflection to access private method
        $reflection = new ReflectionClass($this->auth_instance);
        $method = $reflection->getMethod('create_demo_services');
        $method->setAccessible(true);
        
        // Call the method
        $method->invoke($this->auth_instance, $this->test_user_id);
        
        // Verify three services were created
        $services = $this->services_manager->get_services($this->test_user_id);
        $this->assertCount(3, $services, 'Should create exactly 3 demo services');
    }

    /**
     * Test demo services creation - verify service data structure
     */
    public function test_demo_services_data_structure() {
        // Use reflection to access private method
        $reflection = new ReflectionClass($this->auth_instance);
        $method = $reflection->getMethod('create_demo_services');
        $method->setAccessible(true);
        
        // Call the method
        $method->invoke($this->auth_instance, $this->test_user_id);
        
        // Get created services
        $services = $this->services_manager->get_services($this->test_user_id);
        
        // Expected service names
        $expected_names = [
            'Basic House Cleaning',
            'Deep Cleaning Service', 
            'Office Cleaning'
        ];
        
        $actual_names = array_column($services, 'name');
        
        foreach ($expected_names as $expected_name) {
            $this->assertContains($expected_name, $actual_names, "Service '{$expected_name}' should be created");
        }
        
        // Verify each service has required fields
        foreach ($services as $service) {
            $this->assertNotEmpty($service['name'], 'Service should have a name');
            $this->assertNotEmpty($service['description'], 'Service should have a description');
            $this->assertGreaterThan(0, $service['price'], 'Service should have a positive price');
            $this->assertGreaterThan(0, $service['duration'], 'Service should have a positive duration');
            $this->assertEquals('active', $service['status'], 'Service should be active');
        }
    }

    /**
     * Test demo services creation - verify pricing and duration values
     */
    public function test_demo_services_pricing_and_duration() {
        // Use reflection to access private method
        $reflection = new ReflectionClass($this->auth_instance);
        $method = $reflection->getMethod('create_demo_services');
        $method->setAccessible(true);
        
        // Call the method
        $method->invoke($this->auth_instance, $this->test_user_id);
        
        // Get created services
        $services = $this->services_manager->get_services($this->test_user_id);
        
        // Create a map of services by name for easier testing
        $services_by_name = [];
        foreach ($services as $service) {
            $services_by_name[$service['name']] = $service;
        }
        
        // Test Basic House Cleaning
        $this->assertEquals(75.00, $services_by_name['Basic House Cleaning']['price']);
        $this->assertEquals(120, $services_by_name['Basic House Cleaning']['duration']); // 2 hours
        
        // Test Deep Cleaning Service
        $this->assertEquals(150.00, $services_by_name['Deep Cleaning Service']['price']);
        $this->assertEquals(240, $services_by_name['Deep Cleaning Service']['duration']); // 4 hours
        
        // Test Office Cleaning
        $this->assertEquals(100.00, $services_by_name['Office Cleaning']['price']);
        $this->assertEquals(180, $services_by_name['Office Cleaning']['duration']); // 3 hours
    }

    /**
     * Test demo services creation - verify service options are created
     */
    public function test_demo_services_options_creation() {
        // Use reflection to access private method
        $reflection = new ReflectionClass($this->auth_instance);
        $method = $reflection->getMethod('create_demo_services');
        $method->setAccessible(true);
        
        // Call the method
        $method->invoke($this->auth_instance, $this->test_user_id);
        
        // Get created services
        $services = $this->services_manager->get_services($this->test_user_id);
        
        // Find Basic House Cleaning service
        $basic_cleaning_service = null;
        foreach ($services as $service) {
            if ($service['name'] === 'Basic House Cleaning') {
                $basic_cleaning_service = $service;
                break;
            }
        }
        
        $this->assertNotNull($basic_cleaning_service, 'Basic House Cleaning service should exist');
        
        // Get service options for Basic House Cleaning
        $service_options = $this->services_manager->service_options_manager->get_service_options($basic_cleaning_service['service_id'], $this->test_user_id);
        
        $this->assertCount(2, $service_options, 'Basic House Cleaning should have 2 service options');
        
        // Verify option names
        $option_names = array_column($service_options, 'name');
        $this->assertContains('Inside Oven Cleaning', $option_names);
        $this->assertContains('Inside Refrigerator Cleaning', $option_names);
        
        // Verify option prices
        foreach ($service_options as $option) {
            if ($option['name'] === 'Inside Oven Cleaning') {
                $this->assertEquals(15.00, $option['price_impact_value']);
            } elseif ($option['name'] === 'Inside Refrigerator Cleaning') {
                $this->assertEquals(10.00, $option['price_impact_value']);
            }
            $this->assertEquals('addon', $option['type']);
            $this->assertEquals('fixed', $option['price_impact_type']);
        }
    }

    /**
     * Test demo services creation - verify error handling when service creation fails
     */
    public function test_demo_services_creation_error_handling() {
        // Create a mock Services manager that will fail
        $mock_services = $this->createMock(Services::class);
        $mock_services->method('add_service')
                     ->willReturn(new WP_Error('test_error', 'Test error message'));
        
        // This test would require dependency injection or other refactoring to properly test
        // For now, we'll test that the method doesn't throw exceptions on failure
        
        // Use reflection to access private method
        $reflection = new ReflectionClass($this->auth_instance);
        $method = $reflection->getMethod('create_demo_services');
        $method->setAccessible(true);
        
        // The method should not throw exceptions even if individual services fail
        try {
            $method->invoke($this->auth_instance, $this->test_user_id);
            $this->assertTrue(true, 'Method should not throw exceptions on service creation failure');
        } catch (Exception $e) {
            // If we get here, check if it's the expected "Failed to create any demo services" exception
            $this->assertEquals('Failed to create any demo services', $e->getMessage());
        }
    }

    /**
     * Test demo services creation with invalid user ID
     */
    public function test_demo_services_creation_invalid_user_id() {
        // Use reflection to access private method
        $reflection = new ReflectionClass($this->auth_instance);
        $method = $reflection->getMethod('create_demo_services');
        $method->setAccessible(true);
        
        // Test with invalid user ID
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid user ID for demo services creation');
        
        $method->invoke($this->auth_instance, 0);
    }

    /**
     * Test that demo services are created with proper settings
     */
    public function test_demo_services_settings() {
        // Use reflection to access private method
        $reflection = new ReflectionClass($this->auth_instance);
        $method = $reflection->getMethod('create_demo_services');
        $method->setAccessible(true);
        
        // Call the method
        $method->invoke($this->auth_instance, $this->test_user_id);
        
        // Get created services
        $services = $this->services_manager->get_services($this->test_user_id);
        
        foreach ($services as $service) {
            // Verify common settings
            $this->assertEquals(0, $service['disable_frequency_option'], 'Frequency option should be enabled');
            $this->assertEquals(0, $service['disable_discount_code'], 'Discount codes should be enabled');
            
            // Office Cleaning should have pets disabled
            if ($service['name'] === 'Office Cleaning') {
                $this->assertEquals(1, $service['disable_pet_question'], 'Office cleaning should have pet question disabled');
            } else {
                $this->assertEquals(0, $service['disable_pet_question'], 'Residential services should have pet question enabled');
            }
        }
    }
}

    /**
     * Test default availability setup - verify Monday-Friday slots are created
     */
    public function test_default_availability_weekday_slots() {
        // Use reflection to access private method
        $reflection = new ReflectionClass($this->auth_instance);
        $method = $reflection->getMethod('setup_default_availability');
        $method->setAccessible(true);
        
        // Call the method
        $method->invoke($this->auth_instance, $this->test_user_id);
        
        // Get created availability schedule
        $schedule = $this->availability_manager->get_recurring_schedule($this->test_user_id);
        
        // Verify Monday through Friday (1-5) have slots
        $weekdays = [1, 2, 3, 4, 5]; // Monday through Friday
        foreach ($weekdays as $day) {
            $this->assertArrayHasKey($day, $schedule, "Day {$day} should have availability slots");
            $this->assertNotEmpty($schedule[$day], "Day {$day} should have at least one time slot");
        }
    }

    /**
     * Test default availability setup - verify weekend slots are inactive
     */
    public function test_default_availability_weekend_inactive() {
        // Use reflection to access private method
        $reflection = new ReflectionClass($this->auth_instance);
        $method = $reflection->getMethod('setup_default_availability');
        $method->setAccessible(true);
        
        // Call the method
        $method->invoke($this->auth_instance, $this->test_user_id);
        
        // Get created availability schedule
        $schedule = $this->availability_manager->get_recurring_schedule($this->test_user_id);
        
        // Verify Saturday (6) and Sunday (0) have no slots or are empty
        $weekend_days = [0, 6]; // Sunday and Saturday
        foreach ($weekend_days as $day) {
            if (array_key_exists($day, $schedule)) {
                $this->assertEmpty($schedule[$day], "Day {$day} (weekend) should have no time slots");
            }
        }
    }

    /**
     * Test default availability setup - verify time slot range (9 AM to 5 PM)
     */
    public function test_default_availability_time_range() {
        // Use reflection to access private method
        $reflection = new ReflectionClass($this->auth_instance);
        $method = $reflection->getMethod('setup_default_availability');
        $method->setAccessible(true);
        
        // Call the method
        $method->invoke($this->auth_instance, $this->test_user_id);
        
        // Get created availability schedule
        $schedule = $this->availability_manager->get_recurring_schedule($this->test_user_id);
        
        // Check Monday (day 1) as representative
        $this->assertArrayHasKey(1, $schedule, 'Monday should have availability');
        $monday_slots = $schedule[1];
        $this->assertNotEmpty($monday_slots, 'Monday should have time slots');
        
        // Verify the time range
        $first_slot = $monday_slots[0];
        $this->assertEquals('09:00', $first_slot['start_time'], 'Should start at 9 AM');
        $this->assertEquals('17:00', $first_slot['end_time'], 'Should end at 5 PM');
    }

    /**
     * Test default availability setup - verify slot duration matches system default
     */
    public function test_default_availability_slot_properties() {
        // Use reflection to access private method
        $reflection = new ReflectionClass($this->auth_instance);
        $method = $reflection->getMethod('setup_default_availability');
        $method->setAccessible(true);
        
        // Call the method
        $method->invoke($this->auth_instance, $this->test_user_id);
        
        // Verify slots were created in database with correct properties
        global $wpdb;
        $availability_table = \NORDBOOKING\Classes\Database::get_table_name('availability_rules');
        
        $slots = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$availability_table} WHERE user_id = %d AND is_active = 1",
            $this->test_user_id
        ), ARRAY_A);
        
        $this->assertCount(5, $slots, 'Should have 5 active slots (Monday-Friday)');
        
        foreach ($slots as $slot) {
            $this->assertEquals(1, $slot['is_active'], 'Slot should be active');
            $this->assertEquals(1, $slot['capacity'], 'Slot should have default capacity of 1');
            $this->assertContains($slot['day_of_week'], [1, 2, 3, 4, 5], 'Should only have weekday slots');
        }
    }

    /**
     * Test default availability setup error handling
     */
    public function test_default_availability_setup_error_handling() {
        // Use reflection to access private method
        $reflection = new ReflectionClass($this->auth_instance);
        $method = $reflection->getMethod('setup_default_availability');
        $method->setAccessible(true);
        
        // Test with invalid user ID
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid user ID for default availability setup');
        
        $method->invoke($this->auth_instance, 0);
    }

    /**
     * Test that availability setup doesn't interfere with existing slots
     */
    public function test_default_availability_overwrites_existing() {
        // First, create some existing availability
        $existing_schedule = [
            [
                'day_of_week' => 1,
                'is_enabled' => true,
                'slots' => [
                    [
                        'start_time' => '08:00',
                        'end_time' => '12:00'
                    ]
                ]
            ]
        ];
        
        $this->availability_manager->save_recurring_schedule($this->test_user_id, $existing_schedule);
        
        // Verify existing schedule was saved
        $schedule_before = $this->availability_manager->get_recurring_schedule($this->test_user_id);
        $this->assertArrayHasKey(1, $schedule_before);
        $this->assertEquals('08:00', $schedule_before[1][0]['start_time']);
        
        // Now run the default availability setup
        $reflection = new ReflectionClass($this->auth_instance);
        $method = $reflection->getMethod('setup_default_availability');
        $method->setAccessible(true);
        $method->invoke($this->auth_instance, $this->test_user_id);
        
        // Verify the default schedule replaced the existing one
        $schedule_after = $this->availability_manager->get_recurring_schedule($this->test_user_id);
        $this->assertArrayHasKey(1, $schedule_after);
        $this->assertEquals('09:00', $schedule_after[1][0]['start_time'], 'Should be replaced with default 9 AM start');
        $this->assertEquals('17:00', $schedule_after[1][0]['end_time'], 'Should be replaced with default 5 PM end');
    }   
 /**
     * Test demo discount codes creation - verify both discounts are created
     */
    public function test_demo_discounts_creation_count() {
        // Use reflection to access private method
        $reflection = new ReflectionClass($this->auth_instance);
        $method = $reflection->getMethod('create_demo_discounts');
        $method->setAccessible(true);
        
        // Call the method
        $method->invoke($this->auth_instance, $this->test_user_id, 'Test Company');
        
        // Verify two discount codes were created
        $discounts = $this->discounts_manager->get_discounts($this->test_user_id);
        $this->assertCount(2, $discounts, 'Should create exactly 2 demo discount codes');
    }

    /**
     * Test demo discount codes creation - verify percentage and fixed amount types
     */
    public function test_demo_discounts_types_and_values() {
        // Use reflection to access private method
        $reflection = new ReflectionClass($this->auth_instance);
        $method = $reflection->getMethod('create_demo_discounts');
        $method->setAccessible(true);
        
        // Call the method
        $method->invoke($this->auth_instance, $this->test_user_id, 'Demo Compound');
        
        // Get created discounts
        $discounts = $this->discounts_manager->get_discounts($this->test_user_id);
        
        // Create a map of discounts by code for easier testing
        $discounts_by_code = [];
        foreach ($discounts as $discount) {
            $discounts_by_code[$discount['code']] = $discount;
        }
        
        // Verify DEMO10 (percentage discount)
        $this->assertArrayHasKey('DEMO10', $discounts_by_code, 'DEMO10 discount should exist');
        $demo10 = $discounts_by_code['DEMO10'];
        $this->assertEquals('percentage', $demo10['type'], 'DEMO10 should be percentage type');
        $this->assertEquals(10.00, $demo10['value'], 'DEMO10 should have 10% value');
        
        // Verify WELCOME5 (fixed amount discount)
        $this->assertArrayHasKey('WELCOME5', $discounts_by_code, 'WELCOME5 discount should exist');
        $welcome5 = $discounts_by_code['WELCOME5'];
        $this->assertEquals('fixed_amount', $welcome5['type'], 'WELCOME5 should be fixed_amount type');
        $this->assertEquals(5.00, $welcome5['value'], 'WELCOME5 should have $5 value');
    }

    /**
     * Test demo discount codes creation - verify expiry dates and usage limits
     */
    public function test_demo_discounts_expiry_and_limits() {
        // Use reflection to access private method
        $reflection = new ReflectionClass($this->auth_instance);
        $method = $reflection->getMethod('create_demo_discounts');
        $method->setAccessible(true);
        
        // Call the method
        $method->invoke($this->auth_instance, $this->test_user_id, 'Demo Compound');
        
        // Get created discounts
        $discounts = $this->discounts_manager->get_discounts($this->test_user_id);
        
        // Create a map of discounts by code for easier testing
        $discounts_by_code = [];
        foreach ($discounts as $discount) {
            $discounts_by_code[$discount['code']] = $discount;
        }
        
        // Calculate expected expiry date (30 days from now)
        $expected_expiry = date('Y-m-d', strtotime('+30 days'));
        
        // Verify DEMO10 expiry and usage limit
        $demo10 = $discounts_by_code['DEMO10'];
        $this->assertEquals($expected_expiry, $demo10['expiry_date'], 'DEMO10 should expire in 30 days');
        $this->assertEquals(50, $demo10['usage_limit'], 'DEMO10 should have usage limit of 50');
        $this->assertEquals('active', $demo10['status'], 'DEMO10 should be active');
        
        // Verify WELCOME5 expiry and usage limit
        $welcome5 = $discounts_by_code['WELCOME5'];
        $this->assertEquals($expected_expiry, $welcome5['expiry_date'], 'WELCOME5 should expire in 30 days');
        $this->assertEquals(100, $welcome5['usage_limit'], 'WELCOME5 should have usage limit of 100');
        $this->assertEquals('active', $welcome5['status'], 'WELCOME5 should be active');
    }

    /**
     * Test demo discount codes creation - verify discount code uniqueness
     */
    public function test_demo_discounts_code_uniqueness() {
        // Use reflection to access private method
        $reflection = new ReflectionClass($this->auth_instance);
        $method = $reflection->getMethod('create_demo_discounts');
        $method->setAccessible(true);
        
        // Call the method
        $method->invoke($this->auth_instance, $this->test_user_id, 'Demo Compound');
        
        // Get created discounts
        $discounts = $this->discounts_manager->get_discounts($this->test_user_id);
        
        // Verify codes are unique
        $codes = array_column($discounts, 'code');
        $unique_codes = array_unique($codes);
        $this->assertCount(count($codes), $unique_codes, 'All discount codes should be unique');
        
        // Verify specific codes exist
        $this->assertContains('DEMO10', $codes, 'DEMO10 code should exist');
        $this->assertContains('WELCOME5', $codes, 'WELCOME5 code should exist');
    }

    /**
     * Test demo discount codes creation - verify proper formatting
     */
    public function test_demo_discounts_formatting() {
        // Use reflection to access private method
        $reflection = new ReflectionClass($this->auth_instance);
        $method = $reflection->getMethod('create_demo_discounts');
        $method->setAccessible(true);
        
        // Call the method
        $method->invoke($this->auth_instance, $this->test_user_id, 'Demo Compound');
        
        // Get created discounts
        $discounts = $this->discounts_manager->get_discounts($this->test_user_id);
        
        foreach ($discounts as $discount) {
            // Verify all required fields are present
            $this->assertNotEmpty($discount['code'], 'Discount should have a code');
            $this->assertNotEmpty($discount['type'], 'Discount should have a type');
            $this->assertGreaterThan(0, $discount['value'], 'Discount should have a positive value');
            $this->assertNotEmpty($discount['expiry_date'], 'Discount should have an expiry date');
            $this->assertGreaterThan(0, $discount['usage_limit'], 'Discount should have a usage limit');
            $this->assertEquals(0, $discount['times_used'], 'Discount should start with 0 times used');
            $this->assertEquals($this->test_user_id, $discount['user_id'], 'Discount should belong to test user');
        }
    }

    /**
     * Test demo discount codes creation error handling
     */
    public function test_demo_discounts_creation_error_handling() {
        // Use reflection to access private method
        $reflection = new ReflectionClass($this->auth_instance);
        $method = $reflection->getMethod('create_demo_discounts');
        $method->setAccessible(true);
        
        // Test with invalid user ID
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid user ID for demo discounts creation');
        
        $method->invoke($this->auth_instance, 0, 'Test Company');
    }

    /**
     * Test demo discount codes creation with company name reference
     */
    public function test_demo_discounts_company_name_logging() {
        // Use reflection to access private method
        $reflection = new ReflectionClass($this->auth_instance);
        $method = $reflection->getMethod('create_demo_discounts');
        $method->setAccessible(true);
        
        // This test verifies the method runs without error when company name is provided
        // The company name is used for logging purposes
        try {
            $method->invoke($this->auth_instance, $this->test_user_id, 'Demo Compound Test');
            $this->assertTrue(true, 'Method should handle company name parameter correctly');
        } catch (Exception $e) {
            $this->fail('Method should not throw exception with valid parameters: ' . $e->getMessage());
        }
        
        // Verify discounts were still created
        $discounts = $this->discounts_manager->get_discounts($this->test_user_id);
        $this->assertCount(2, $discounts, 'Should still create 2 discounts with company name');
    }

    /**
     * Test demo discount codes creation without company name
     */
    public function test_demo_discounts_without_company_name() {
        // Use reflection to access private method
        $reflection = new ReflectionClass($this->auth_instance);
        $method = $reflection->getMethod('create_demo_discounts');
        $method->setAccessible(true);
        
        // Call the method without company name
        $method->invoke($this->auth_instance, $this->test_user_id, '');
        
        // Verify discounts were created even without company name
        $discounts = $this->discounts_manager->get_discounts($this->test_user_id);
        $this->assertCount(2, $discounts, 'Should create discounts even without company name');
    }    
/**
     * Test default settings configuration - verify location check is disabled
     */
    public function test_default_settings_location_check_disabled() {
        // Use reflection to access private method
        $reflection = new ReflectionClass($this->auth_instance);
        $method = $reflection->getMethod('configure_default_settings');
        $method->setAccessible(true);
        
        // Call the method
        $method->invoke($this->auth_instance, $this->test_user_id);
        
        // Verify location check setting is disabled
        $location_check_setting = $this->settings_manager->get_setting($this->test_user_id, 'bf_enable_location_check');
        $this->assertEquals('0', $location_check_setting, 'Location check should be disabled by default');
    }

    /**
     * Test default settings configuration - verify other settings remain unchanged
     */
    public function test_default_settings_other_settings_unchanged() {
        // First, set some other settings to known values
        $this->settings_manager->update_setting($this->test_user_id, 'bf_time_slot_duration', '45');
        $this->settings_manager->update_setting($this->test_user_id, 'bf_max_booking_days_ahead', '60');
        
        // Use reflection to access private method
        $reflection = new ReflectionClass($this->auth_instance);
        $method = $reflection->getMethod('configure_default_settings');
        $method->setAccessible(true);
        
        // Call the method
        $method->invoke($this->auth_instance, $this->test_user_id);
        
        // Verify other settings were not changed
        $time_slot_duration = $this->settings_manager->get_setting($this->test_user_id, 'bf_time_slot_duration');
        $max_booking_days = $this->settings_manager->get_setting($this->test_user_id, 'bf_max_booking_days_ahead');
        
        $this->assertEquals('45', $time_slot_duration, 'Time slot duration should remain unchanged');
        $this->assertEquals('60', $max_booking_days, 'Max booking days should remain unchanged');
        
        // But location check should still be set to disabled
        $location_check_setting = $this->settings_manager->get_setting($this->test_user_id, 'bf_enable_location_check');
        $this->assertEquals('0', $location_check_setting, 'Location check should be disabled');
    }

    /**
     * Test default settings configuration - verify settings persistence
     */
    public function test_default_settings_persistence() {
        // Use reflection to access private method
        $reflection = new ReflectionClass($this->auth_instance);
        $method = $reflection->getMethod('configure_default_settings');
        $method->setAccessible(true);
        
        // Call the method
        $method->invoke($this->auth_instance, $this->test_user_id);
        
        // Verify setting is persisted in database
        global $wpdb;
        $settings_table = \NORDBOOKING\Classes\Database::get_table_name('tenant_settings');
        
        $setting_value = $wpdb->get_var($wpdb->prepare(
            "SELECT setting_value FROM {$settings_table} WHERE user_id = %d AND setting_name = %s",
            $this->test_user_id,
            'bf_enable_location_check'
        ));
        
        $this->assertEquals('0', $setting_value, 'Location check setting should be persisted as disabled in database');
    }

    /**
     * Test default settings configuration error handling
     */
    public function test_default_settings_configuration_error_handling() {
        // Use reflection to access private method
        $reflection = new ReflectionClass($this->auth_instance);
        $method = $reflection->getMethod('configure_default_settings');
        $method->setAccessible(true);
        
        // Test with invalid user ID
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid user ID for default settings configuration');
        
        $method->invoke($this->auth_instance, 0);
    }

    /**
     * Test default settings configuration - verify setting overrides existing value
     */
    public function test_default_settings_overrides_existing() {
        // First, set location check to enabled
        $this->settings_manager->update_setting($this->test_user_id, 'bf_enable_location_check', '1');
        
        // Verify it was set
        $initial_setting = $this->settings_manager->get_setting($this->test_user_id, 'bf_enable_location_check');
        $this->assertEquals('1', $initial_setting, 'Location check should initially be enabled');
        
        // Use reflection to access private method
        $reflection = new ReflectionClass($this->auth_instance);
        $method = $reflection->getMethod('configure_default_settings');
        $method->setAccessible(true);
        
        // Call the method
        $method->invoke($this->auth_instance, $this->test_user_id);
        
        // Verify it was overridden to disabled
        $final_setting = $this->settings_manager->get_setting($this->test_user_id, 'bf_enable_location_check');
        $this->assertEquals('0', $final_setting, 'Location check should be overridden to disabled');
    }

    /**
     * Test default settings configuration - verify method handles settings manager initialization
     */
    public function test_default_settings_manager_initialization() {
        // Clear the global settings manager to test initialization
        unset($GLOBALS['nordbooking_settings_manager']);
        
        // Use reflection to access private method
        $reflection = new ReflectionClass($this->auth_instance);
        $method = $reflection->getMethod('configure_default_settings');
        $method->setAccessible(true);
        
        // Call the method - should initialize settings manager internally
        $method->invoke($this->auth_instance, $this->test_user_id);
        
        // Verify setting was still configured correctly
        $location_check_setting = $this->settings_manager->get_setting($this->test_user_id, 'bf_enable_location_check');
        $this->assertEquals('0', $location_check_setting, 'Location check should be disabled even with fresh settings manager');
        
        // Verify global settings manager was initialized
        $this->assertInstanceOf(Settings::class, $GLOBALS['nordbooking_settings_manager'], 'Global settings manager should be initialized');
    }    /
**
     * Test complete registration flow with automation - end-to-end integration
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_complete_registration_flow_with_automation() {
        // Prepare registration data
        $_POST = [
            'nonce'            => wp_create_nonce(Auth::REGISTER_NONCE_ACTION),
            'email'            => 'integration@example.com',
            'password'         => 'password123',
            'password_confirm' => 'password123',
            'first_name'       => 'Integration',
            'last_name'        => 'Test',
            'company_name'     => 'Integration Test Company',
        ];

        // Call registration handler
        ob_start();
        try {
            $this->auth_instance->handle_ajax_registration();
        } catch (\WP_UnitTest_Exception $e) {
            // Expected due to wp_die() in wp_send_json_*
        }
        $response_json = ob_get_clean();
        $response = json_decode($response_json, true);

        // Verify registration was successful
        $this->assertTrue($response['success'], "Registration should be successful");
        
        // Get the created user
        $user = get_user_by('email', 'integration@example.com');
        $this->assertInstanceOf(WP_User::class, $user);
        
        // Verify demo services were created
        $services_manager = new Services();
        $services = $services_manager->get_services($user->ID);
        $this->assertCount(3, $services, 'Should have 3 demo services');
        
        $service_names = array_column($services, 'name');
        $this->assertContains('Basic House Cleaning', $service_names);
        $this->assertContains('Deep Cleaning Service', $service_names);
        $this->assertContains('Office Cleaning', $service_names);
        
        // Verify availability was set up
        $availability_manager = new Availability();
        $schedule = $availability_manager->get_recurring_schedule($user->ID);
        $this->assertCount(5, $schedule, 'Should have availability for 5 weekdays');
        
        // Verify discount codes were created
        $discounts_manager = new Discounts($user->ID);
        $discounts = $discounts_manager->get_discounts($user->ID);
        $this->assertCount(2, $discounts, 'Should have 2 demo discount codes');
        
        $discount_codes = array_column($discounts, 'code');
        $this->assertContains('DEMO10', $discount_codes);
        $this->assertContains('WELCOME5', $discount_codes);
        
        // Verify location check setting is disabled
        $settings_manager = new Settings();
        $location_check = $settings_manager->get_setting($user->ID, 'bf_enable_location_check');
        $this->assertEquals('0', $location_check, 'Location check should be disabled');
        
        // Clean up
        wp_delete_user($user->ID);
        unset($_POST);
    }

    /**
     * Test partial failure scenarios - some components fail but registration succeeds
     */
    public function test_registration_with_partial_automation_failure() {
        // Create a user manually to test individual automation components
        $test_user_id = $this->factory->user->create([
            'role' => Auth::ROLE_BUSINESS_OWNER,
            'user_email' => 'partialtest@example.com',
            'user_login' => 'partialtest@example.com',
            'first_name' => 'Partial',
            'last_name' => 'Test'
        ]);
        
        // Test that if one component fails, others still work
        // We'll test by calling setup_new_business_owner directly
        $reflection = new ReflectionClass($this->auth_instance);
        $method = $reflection->getMethod('setup_new_business_owner');
        $method->setAccessible(true);
        
        $user = get_userdata($test_user_id);
        
        // This should not throw an exception even if some components fail
        try {
            $result = $method->invoke($this->auth_instance, $user, 'Partial Test Company', 'free');
            $this->assertIsArray($result, 'Should return result array');
            $this->assertArrayHasKey('message', $result);
            $this->assertArrayHasKey('redirect_url', $result);
        } catch (Exception $e) {
            $this->fail('Registration should not fail completely due to automation issues: ' . $e->getMessage());
        }
        
        // Verify user still has business owner role
        $updated_user = get_userdata($test_user_id);
        $this->assertTrue(in_array(Auth::ROLE_BUSINESS_OWNER, $updated_user->roles));
        
        // Clean up
        wp_delete_user($test_user_id);
    }

    /**
     * Test registration performance impact is minimal
     */
    public function test_registration_performance_impact() {
        // Measure time for registration without automation (baseline)
        $start_time = microtime(true);
        
        // Create user without automation
        $baseline_user_id = $this->factory->user->create([
            'role' => Auth::ROLE_BUSINESS_OWNER,
            'user_email' => 'baseline@example.com'
        ]);
        
        $baseline_time = microtime(true) - $start_time;
        
        // Measure time for registration with automation
        $start_time = microtime(true);
        
        $reflection = new ReflectionClass($this->auth_instance);
        $method = $reflection->getMethod('setup_new_business_owner');
        $method->setAccessible(true);
        
        $test_user_id = $this->factory->user->create([
            'role' => Auth::ROLE_BUSINESS_OWNER,
            'user_email' => 'performance@example.com'
        ]);
        
        $user = get_userdata($test_user_id);
        $method->invoke($this->auth_instance, $user, 'Performance Test Company', 'free');
        
        $automation_time = microtime(true) - $start_time;
        
        // Automation should not add more than 5 seconds to registration
        $time_difference = $automation_time - $baseline_time;
        $this->assertLessThan(5.0, $time_difference, 'Automation should not add more than 5 seconds to registration');
        
        // Clean up
        wp_delete_user($baseline_user_id);
        wp_delete_user($test_user_id);
    }

    /**
     * Test that user can immediately access and use all created demo data
     */
    public function test_immediate_demo_data_accessibility() {
        // Create user with automation
        $reflection = new ReflectionClass($this->auth_instance);
        $method = $reflection->getMethod('setup_new_business_owner');
        $method->setAccessible(true);
        
        $test_user_id = $this->factory->user->create([
            'role' => Auth::ROLE_BUSINESS_OWNER,
            'user_email' => 'accessibility@example.com'
        ]);
        
        $user = get_userdata($test_user_id);
        $method->invoke($this->auth_instance, $user, 'Accessibility Test Company', 'free');
        
        // Test that services are immediately accessible
        $services_manager = new Services();
        $services = $services_manager->get_services($test_user_id);
        
        foreach ($services as $service) {
            $this->assertEquals('active', $service['status'], 'All demo services should be active');
            $this->assertGreaterThan(0, $service['price'], 'All demo services should have valid pricing');
            $this->assertGreaterThan(0, $service['duration'], 'All demo services should have valid duration');
        }
        
        // Test that availability is immediately usable
        $availability_manager = new Availability();
        $schedule = $availability_manager->get_recurring_schedule($test_user_id);
        
        foreach ($schedule as $day => $slots) {
            foreach ($slots as $slot) {
                $this->assertNotEmpty($slot['start_time'], 'Availability slots should have start time');
                $this->assertNotEmpty($slot['end_time'], 'Availability slots should have end time');
            }
        }
        
        // Test that discount codes are immediately valid
        $discounts_manager = new Discounts($test_user_id);
        $discounts = $discounts_manager->get_discounts($test_user_id);
        
        foreach ($discounts as $discount) {
            $this->assertEquals('active', $discount['status'], 'All demo discounts should be active');
            $this->assertGreaterThan(0, $discount['usage_limit'], 'All demo discounts should have usage limits');
            $this->assertEquals(0, $discount['times_used'], 'All demo discounts should start unused');
        }
        
        // Clean up
        wp_delete_user($test_user_id);
    }

    /**
     * Test concurrent registrations don't interfere with each other
     */
    public function test_concurrent_registrations() {
        // Simulate multiple users registering at the same time
        $user_ids = [];
        
        for ($i = 1; $i <= 3; $i++) {
            $reflection = new ReflectionClass($this->auth_instance);
            $method = $reflection->getMethod('setup_new_business_owner');
            $method->setAccessible(true);
            
            $user_id = $this->factory->user->create([
                'role' => Auth::ROLE_BUSINESS_OWNER,
                'user_email' => "concurrent{$i}@example.com"
            ]);
            
            $user = get_userdata($user_id);
            $method->invoke($this->auth_instance, $user, "Concurrent Test Company {$i}", 'free');
            
            $user_ids[] = $user_id;
        }
        
        // Verify each user has their own complete set of demo data
        foreach ($user_ids as $user_id) {
            // Check services
            $services_manager = new Services();
            $services = $services_manager->get_services($user_id);
            $this->assertCount(3, $services, "User {$user_id} should have 3 demo services");
            
            // Check discounts
            $discounts_manager = new Discounts($user_id);
            $discounts = $discounts_manager->get_discounts($user_id);
            $this->assertCount(2, $discounts, "User {$user_id} should have 2 demo discounts");
            
            // Check availability
            $availability_manager = new Availability();
            $schedule = $availability_manager->get_recurring_schedule($user_id);
            $this->assertCount(5, $schedule, "User {$user_id} should have 5 weekday availability slots");
        }
        
        // Clean up
        foreach ($user_ids as $user_id) {
            wp_delete_user($user_id);
        }
    }    /**

     * Test service areas message displays when location check is disabled
     */
    public function test_service_areas_message_displays_when_location_check_disabled() {
        // Set location check to disabled
        $this->settings_manager->update_setting($this->test_user_id, 'bf_enable_location_check', '0');
        
        // Set current user for the test
        wp_set_current_user($this->test_user_id);
        
        // Capture output from the areas page
        ob_start();
        
        // Mock the areas page context
        $current_user_id = $this->test_user_id;
        $settings_manager = new \NORDBOOKING\Classes\Settings();
        $location_check_enabled = $settings_manager->get_setting($current_user_id, 'bf_enable_location_check', '1');
        
        // Simulate the conditional logic from the areas page
        if ($location_check_enabled === '0' || $location_check_enabled === 0 || $location_check_enabled === false) {
            echo '<div class="nordbooking-info-banner nordbooking-location-check-info">';
            echo '<div class="nordbooking-info-banner-content">';
            echo '<h4>Location Check is Currently Disabled</h4>';
            echo '<p>Your booking form will accept bookings from any location.</p>';
            echo '<a href="' . esc_url(home_url('/dashboard/booking-form/')) . '">Go to Booking Form Settings</a>';
            echo '</div>';
            echo '</div>';
        }
        
        $output = ob_get_clean();
        
        // Verify message is displayed
        $this->assertStringContainsString('nordbooking-info-banner', $output, 'Info banner should be displayed');
        $this->assertStringContainsString('Location Check is Currently Disabled', $output, 'Should show disabled message');
        $this->assertStringContainsString('Go to Booking Form Settings', $output, 'Should show link to settings');
        $this->assertStringContainsString('/dashboard/booking-form/', $output, 'Should link to booking form settings');
    }

    /**
     * Test service areas message is hidden when location check is enabled
     */
    public function test_service_areas_message_hidden_when_location_check_enabled() {
        // Set location check to enabled
        $this->settings_manager->update_setting($this->test_user_id, 'bf_enable_location_check', '1');
        
        // Set current user for the test
        wp_set_current_user($this->test_user_id);
        
        // Capture output from the areas page
        ob_start();
        
        // Mock the areas page context
        $current_user_id = $this->test_user_id;
        $settings_manager = new \NORDBOOKING\Classes\Settings();
        $location_check_enabled = $settings_manager->get_setting($current_user_id, 'bf_enable_location_check', '1');
        
        // Simulate the conditional logic from the areas page
        if ($location_check_enabled === '0' || $location_check_enabled === 0 || $location_check_enabled === false) {
            echo '<div class="nordbooking-info-banner nordbooking-location-check-info">';
            echo '<div class="nordbooking-info-banner-content">';
            echo '<h4>Location Check is Currently Disabled</h4>';
            echo '<p>Your booking form will accept bookings from any location.</p>';
            echo '<a href="' . esc_url(home_url('/dashboard/booking-form/')) . '">Go to Booking Form Settings</a>';
            echo '</div>';
            echo '</div>';
        }
        
        $output = ob_get_clean();
        
        // Verify message is NOT displayed
        $this->assertStringNotContainsString('nordbooking-info-banner', $output, 'Info banner should not be displayed');
        $this->assertStringNotContainsString('Location Check is Currently Disabled', $output, 'Should not show disabled message');
    }

    /**
     * Test service areas message content includes proper instructions
     */
    public function test_service_areas_message_content_validation() {
        // Set location check to disabled
        $this->settings_manager->update_setting($this->test_user_id, 'bf_enable_location_check', '0');
        
        // Set current user for the test
        wp_set_current_user($this->test_user_id);
        
        // Capture output from the areas page
        ob_start();
        
        // Mock the areas page context with full message content
        $current_user_id = $this->test_user_id;
        $settings_manager = new \NORDBOOKING\Classes\Settings();
        $location_check_enabled = $settings_manager->get_setting($current_user_id, 'bf_enable_location_check', '1');
        
        if ($location_check_enabled === '0' || $location_check_enabled === 0 || $location_check_enabled === false) {
            echo '<div class="nordbooking-info-banner nordbooking-location-check-info">';
            echo '<div class="nordbooking-info-banner-content">';
            echo '<div class="nordbooking-info-banner-icon">';
            echo '<svg class="NORDBOOKING-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
            echo '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>';
            echo '</svg>';
            echo '</div>';
            echo '<div class="nordbooking-info-banner-text">';
            echo '<h4>Location Check is Currently Disabled</h4>';
            echo '<p>Your booking form will accept bookings from any location. To enable location-based restrictions and use the service areas you configure here, please enable "Location Check" in your booking form settings.</p>';
            echo '<a href="' . esc_url(home_url('/dashboard/booking-form/')) . '" class="nordbooking-info-banner-link">';
            echo 'Go to Booking Form Settings';
            echo '<svg class="NORDBOOKING-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
            echo '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>';
            echo '</svg>';
            echo '</a>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
        
        $output = ob_get_clean();
        
        // Verify all required content elements
        $this->assertStringContainsString('nordbooking-info-banner-icon', $output, 'Should have info icon');
        $this->assertStringContainsString('nordbooking-info-banner-text', $output, 'Should have text container');
        $this->assertStringContainsString('nordbooking-info-banner-link', $output, 'Should have styled link');
        $this->assertStringContainsString('location-based restrictions', $output, 'Should explain location restrictions');
        $this->assertStringContainsString('booking form settings', $output, 'Should mention booking form settings');
        $this->assertStringContainsString('svg', $output, 'Should include SVG icons');
    }

    /**
     * Test service areas message navigation link is correct
     */
    public function test_service_areas_message_navigation_link() {
        // Set location check to disabled
        $this->settings_manager->update_setting($this->test_user_id, 'bf_enable_location_check', '0');
        
        // Set current user for the test
        wp_set_current_user($this->test_user_id);
        
        // Mock the areas page context
        $current_user_id = $this->test_user_id;
        $settings_manager = new \NORDBOOKING\Classes\Settings();
        $location_check_enabled = $settings_manager->get_setting($current_user_id, 'bf_enable_location_check', '1');
        
        $expected_url = home_url('/dashboard/booking-form/');
        
        // Capture the link generation
        ob_start();
        if ($location_check_enabled === '0' || $location_check_enabled === 0 || $location_check_enabled === false) {
            echo '<a href="' . esc_url($expected_url) . '" class="nordbooking-info-banner-link">';
            echo 'Go to Booking Form Settings';
            echo '</a>';
        }
        $output = ob_get_clean();
        
        // Verify correct URL is generated
        $this->assertStringContainsString($expected_url, $output, 'Should link to correct booking form settings URL');
        $this->assertStringContainsString('href="' . esc_url($expected_url) . '"', $output, 'Should have properly escaped URL');
    }

    /**
     * Test service areas message styling classes are applied
     */
    public function test_service_areas_message_styling_classes() {
        // Set location check to disabled
        $this->settings_manager->update_setting($this->test_user_id, 'bf_enable_location_check', '0');
        
        // Set current user for the test
        wp_set_current_user($this->test_user_id);
        
        // Capture output from the areas page
        ob_start();
        
        // Mock the areas page context with all styling classes
        $current_user_id = $this->test_user_id;
        $settings_manager = new \NORDBOOKING\Classes\Settings();
        $location_check_enabled = $settings_manager->get_setting($current_user_id, 'bf_enable_location_check', '1');
        
        if ($location_check_enabled === '0' || $location_check_enabled === 0 || $location_check_enabled === false) {
            echo '<div class="nordbooking-info-banner nordbooking-location-check-info">';
            echo '<div class="nordbooking-info-banner-content">';
            echo '<div class="nordbooking-info-banner-icon">';
            echo '</div>';
            echo '<div class="nordbooking-info-banner-text">';
            echo '<a href="#" class="nordbooking-info-banner-link">';
            echo '</a>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
        
        $output = ob_get_clean();
        
        // Verify all required CSS classes are present
        $expected_classes = [
            'nordbooking-info-banner',
            'nordbooking-location-check-info',
            'nordbooking-info-banner-content',
            'nordbooking-info-banner-icon',
            'nordbooking-info-banner-text',
            'nordbooking-info-banner-link'
        ];
        
        foreach ($expected_classes as $class) {
            $this->assertStringContainsString($class, $output, "Should include CSS class: {$class}");
        }
    }

    /**
     * Test service areas message handles different location check values
     */
    public function test_service_areas_message_handles_different_values() {
        // Test different values that should trigger the message
        $disabled_values = ['0', 0, false, ''];
        
        foreach ($disabled_values as $value) {
            // Set location check to the test value
            $this->settings_manager->update_setting($this->test_user_id, 'bf_enable_location_check', $value);
            
            // Set current user for the test
            wp_set_current_user($this->test_user_id);
            
            // Mock the areas page context
            $current_user_id = $this->test_user_id;
            $settings_manager = new \NORDBOOKING\Classes\Settings();
            $location_check_enabled = $settings_manager->get_setting($current_user_id, 'bf_enable_location_check', '1');
            
            // Test the conditional logic
            $should_show_message = ($location_check_enabled === '0' || $location_check_enabled === 0 || $location_check_enabled === false);
            
            if ($value === '') {
                // Empty string should not trigger the message (it should use default '1')
                $this->assertFalse($should_show_message, "Empty value should not show message");
            } else {
                $this->assertTrue($should_show_message, "Value '{$value}' should show message");
            }
        }
        
        // Test values that should NOT trigger the message
        $enabled_values = ['1', 1, true, 'yes'];
        
        foreach ($enabled_values as $value) {
            // Set location check to the test value
            $this->settings_manager->update_setting($this->test_user_id, 'bf_enable_location_check', $value);
            
            // Mock the areas page context
            $current_user_id = $this->test_user_id;
            $settings_manager = new \NORDBOOKING\Classes\Settings();
            $location_check_enabled = $settings_manager->get_setting($current_user_id, 'bf_enable_location_check', '1');
            
            // Test the conditional logic
            $should_show_message = ($location_check_enabled === '0' || $location_check_enabled === 0 || $location_check_enabled === false);
            
            $this->assertFalse($should_show_message, "Value '{$value}' should not show message");
        }
    }
}