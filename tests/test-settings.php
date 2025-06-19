<?php

use MoBooking\Classes\Settings;
use MoBooking\Classes\Notifications;

/**
 * Class Test_MoBooking_Settings
 *
 * @group mobooking_settings
 */
class Test_MoBooking_Settings extends WP_UnitTestCase {

    protected $settings_manager;
    protected $user_id;
    protected static $default_currency_code = 'USD'; // From Settings::$default_tenant_settings
    protected static $default_user_language = 'en_US'; // From Settings::$default_tenant_settings

    public function setUp(): void {
        parent::setUp();
        $this->user_id = $this->factory->user->create(['role' => 'administrator']); // Or your custom tenant role
        wp_set_current_user($this->user_id);
        $this->settings_manager = new Settings();

        // It's good practice to ensure default settings are initialized for the test user
        // Settings::initialize_default_settings($this->user_id); // Assuming this method exists and sets up all defaults
    }

    public function tearDown(): void {
        parent::tearDown();
        // Clean up any settings or users created if necessary
    }

    /**
     * Test saving and retrieving biz_currency_code.
     */
    public function test_save_and_get_biz_currency_code() {
        // 1. Test saving valid currency codes from the dropdown options
        $valid_codes = ['USD', 'EUR', 'SEK', 'GBP'];
        foreach ($valid_codes as $code) {
            $this->settings_manager->update_setting($this->user_id, 'biz_currency_code', $code);
            $retrieved_code = $this->settings_manager->get_setting($this->user_id, 'biz_currency_code');
            $this->assertEquals($code, $retrieved_code, "Failed to save and retrieve valid currency code: {$code}");
        }

        // 2. Test saving an empty value - expecting default
        $this->settings_manager->update_setting($this->user_id, 'biz_currency_code', '');
        $retrieved_empty = $this->settings_manager->get_setting($this->user_id, 'biz_currency_code');
        $this->assertEquals(self::$default_currency_code, $retrieved_empty, 'Saving an empty currency code should result in default.');

        // 3. Test saving a lowercase valid code (e.g., 'eur') - expecting sanitization to uppercase 'EUR'
        $this->settings_manager->update_setting($this->user_id, 'biz_currency_code', 'eur');
        $retrieved_lower = $this->settings_manager->get_setting($this->user_id, 'biz_currency_code');
        $this->assertEquals('EUR', $retrieved_lower, 'Lowercase "eur" should be sanitized to "EUR".');

        // 4. Test saving an invalid code not in a typical list but matching format (e.g., 'XYZ')
        // The current sanitization (3 uppercase letters) will allow this.
        // UI restricts to dropdown, but direct save should be tested.
        $this->settings_manager->update_setting($this->user_id, 'biz_currency_code', 'XYZ');
        $retrieved_xyz = $this->settings_manager->get_setting($this->user_id, 'biz_currency_code');
        $this->assertEquals('XYZ', $retrieved_xyz, 'Saving "XYZ" (valid format, not in UI list) should be stored as is by current sanitization.');

        // 5. Test saving an invalid code (too short) - expecting default
        $invalid_code_short = 'EU';
        $this->settings_manager->update_setting($this->user_id, 'biz_currency_code', $invalid_code_short);
        $retrieved_code_short = $this->settings_manager->get_setting($this->user_id, 'biz_currency_code');
        $this->assertEquals(self::$default_currency_code, $retrieved_code_short, 'Failed to default an overly short currency code.');

        // 6. Test saving an invalid code (with numbers/symbols) - expecting default
        $invalid_code_symbols = 'U$1';
        $this->settings_manager->update_setting($this->user_id, 'biz_currency_code', $invalid_code_symbols);
        $retrieved_code_symbols = $this->settings_manager->get_setting($this->user_id, 'biz_currency_code');
        $this->assertEquals(self::$default_currency_code, $retrieved_code_symbols, 'Failed to sanitize currency code with symbols and default.');

        // 7. Test retrieving with no setting (should return default)
        $new_user_id = $this->factory->user->create();
        $default_retrieved = $this->settings_manager->get_setting($new_user_id, 'biz_currency_code');
        $this->assertEquals(self::$default_currency_code, $default_retrieved, 'Should retrieve default currency code if none is set.');
    }

    /**
     * Test saving and retrieving biz_user_language.
     */
    public function test_save_and_get_biz_user_language() {
        // 1. Test saving valid language codes from the new dropdown options
        $valid_languages = ['en_US', 'sv_SE', 'nb_NO', 'fi_FI', 'da_DK'];
        foreach ($valid_languages as $lang_code) {
            $this->settings_manager->update_setting($this->user_id, 'biz_user_language', $lang_code);
            $retrieved_lang = $this->settings_manager->get_setting($this->user_id, 'biz_user_language');
            $this->assertEquals($lang_code, $retrieved_lang, "Failed to save and retrieve valid language code: {$lang_code}");
        }

        // 2. Test saving an invalid format (e.g., en-GB instead of en_GB) - expecting default
        $invalid_format_1 = 'en-GB';
        $this->settings_manager->update_setting($this->user_id, 'biz_user_language', $invalid_format_1);
        $retrieved_invalid_1 = $this->settings_manager->get_setting($this->user_id, 'biz_user_language');
        $this->assertEquals(self::$default_user_language, $retrieved_invalid_1, 'Invalid format en-GB should default.');

        // 3. Test saving an invalid too short code (e.g., 'e') - expecting default
        $invalid_format_2 = 'e';
        $this->settings_manager->update_setting($this->user_id, 'biz_user_language', $invalid_format_2);
        $retrieved_invalid_2 = $this->settings_manager->get_setting($this->user_id, 'biz_user_language');
        $this->assertEquals(self::$default_user_language, $retrieved_invalid_2, 'Invalid short code "e" should default.');

        // 4. Test saving an invalid code with numbers (e.g., 'en_U1') - expecting default
        $invalid_format_3 = 'en_U1';
        $this->settings_manager->update_setting($this->user_id, 'biz_user_language', $invalid_format_3);
        $retrieved_invalid_3 = $this->settings_manager->get_setting($this->user_id, 'biz_user_language');
        $this->assertEquals(self::$default_user_language, $retrieved_invalid_3, 'Invalid code with numbers en_U1 should default.');

        // 5. Test retrieving with no setting (should return default)
        $new_user_id = $this->factory->user->create();
        $default_retrieved = $this->settings_manager->get_setting($new_user_id, 'biz_user_language');
        $this->assertEquals(self::$default_user_language, $default_retrieved, 'Should retrieve default user language if none is set.');
    }

    /**
     * Test currency display in notification emails.
     * This requires a way to intercept emails sent by wp_mail.
     */
    public function test_currency_display_in_notification() {
        // Use WP_Mail::get_instance() or similar if your test suite provides a mail catcher.
        // For this conceptual test, we'll assume $this->mailer exists.
        // $this->mailer = new \WPMailCollector(); // Example, actual setup depends on test env.
        // add_filter( 'wp_mail', array( $this->mailer, 'collect' ) );

        $notifications = new Notifications();
        $test_currency_code = 'EUR';
        $test_price = 123.45;

        // Mock or set the biz_currency_code for the user
        $this->settings_manager->update_setting($this->user_id, 'biz_currency_code', $test_currency_code);

        $booking_details = [
            'booking_reference' => 'TESTREF123',
            'service_names' => 'Test Service',
            'booking_date_time' => '2023-01-01 10:00',
            'total_price' => $test_price,
            'customer_name' => 'John Doe',
            'service_address' => '123 Test St'
        ];
        $customer_email = 'customer@example.com';

        // --- Test Customer Confirmation ---
        // Clear previous emails if mailer collects them
        // if (method_exists($this->mailer, 'clear_emails')) $this->mailer->clear_emails();

        $notifications->send_booking_confirmation_customer($booking_details, $customer_email, $this->user_id);

        // $sent_email_customer = $this->mailer->get_last_email(); // Get the last sent email
        // $this->assertNotNull($sent_email_customer, "Customer confirmation email was not sent/captured.");
        // $this->assertStringContainsString("{$test_currency_code} 123.45", $sent_email_customer['body'], "Customer email body does not contain correctly formatted price.");

        // --- Test Admin Confirmation ---
        // if (method_exists($this->mailer, 'clear_emails')) $this->mailer->clear_emails();
        $admin_booking_details = array_merge($booking_details, [
            'customer_email' => $customer_email, 'customer_phone' => '1234567890'
        ]);
        $notifications->send_booking_confirmation_admin($admin_booking_details, $this->user_id);

        // $sent_email_admin = $this->mailer->get_last_email();
        // $this->assertNotNull($sent_email_admin, "Admin confirmation email was not sent/captured.");
        // $this->assertStringContainsString("{$test_currency_code} 123.45", $sent_email_admin['body'], "Admin email body does not contain correctly formatted price.");

        // remove_filter( 'wp_mail', array( $this->mailer, 'collect' ) );
        $this->markTestIncomplete('Email capture mechanism needs to be implemented or confirmed for this test.');
    }

    /**
     * Test language switching for notification emails.
     * This requires dummy .mo files for 'mobooking' text domain.
     * E.g., languages/en_US.mo and languages/sv_SE.mo
     * With a test string like: 'Booking Summary:' translated differently.
     */
    public function test_language_switching_for_notifications() {
        // Assume en_US.mo has "Booking Summary:"
        // Assume sv_SE.mo has "Bokningsöversikt:" for "Booking Summary:" (Example translation)

        // $this->mailer = new \WPMailCollector();
        // add_filter( 'wp_mail', array( $this->mailer, 'collect' ) );

        $notifications = new Notifications();
        $swedish_lang_code = 'sv_SE';
        $english_lang_code = 'en_US';
        $test_string_key = 'Booking Summary:'; // Key used in __()
        $test_string_en = 'Booking Summary:'; // English
        $test_string_sv = 'Bokningsöversikt:'; // Swedish example

        // Set user's language to Swedish
        $this->settings_manager->update_setting($this->user_id, 'biz_user_language', $swedish_lang_code);

        $booking_details = [
            'booking_reference' => 'TESTLANG123', 'service_names' => 'Testtjänst',
            'booking_date_time' => '2023-01-01 10:00', 'total_price' => 50,
            'customer_name' => 'Sven Svensson', 'service_address' => 'Testgatan 1'
        ];
        $customer_email = 'sven@example.com';

        // --- Test Customer Confirmation in Swedish ---
        // if (method_exists($this->mailer, 'clear_emails')) $this->mailer->clear_emails();
        $notifications->send_booking_confirmation_customer($booking_details, $customer_email, $this->user_id);
        // $sent_email_sv = $this->mailer->get_last_email();
        // $this->assertNotNull($sent_email_sv, "Swedish customer email not sent/captured.");
        // $this->assertStringContainsString($test_string_sv, $sent_email_sv['body'], "Email body not in Swedish.");
        // $this->assertStringNotContainsString($test_string_en, $sent_email_sv['body'], "Email body contains English string when Swedish expected.");

        // --- Test Admin Confirmation in Swedish (as tenant is the admin) ---
        // if (method_exists($this->mailer, 'clear_emails')) $this->mailer->clear_emails();
        $admin_booking_details = array_merge($booking_details, ['customer_email' => $customer_email, 'customer_phone' => 'N/A']);
        $notifications->send_booking_confirmation_admin($admin_booking_details, $this->user_id);
        // $sent_admin_email_sv = $this->mailer->get_last_email();
        // $this->assertNotNull($sent_admin_email_sv, "Swedish admin email not sent/captured.");
        // $this->assertStringContainsString($test_string_sv, $sent_admin_email_sv['body'], "Admin email body not in Swedish.");


        // Switch user's language back to English (or site default) and test again
        $this->settings_manager->update_setting($this->user_id, 'biz_user_language', $english_lang_code);
        // if (method_exists($this->mailer, 'clear_emails')) $this->mailer->clear_emails();
        $notifications->send_booking_confirmation_customer($booking_details, $customer_email, $this->user_id);
        // $sent_email_en = $this->mailer->get_last_email();
        // $this->assertNotNull($sent_email_en, "English customer email not sent/captured.");
        // $this->assertStringContainsString($test_string_en, $sent_email_en['body'], "Email body not in English after locale switch back.");
        // $this->assertStringNotContainsString($test_string_sv, $sent_email_en['body'], "Email body contains Swedish string when English expected.");

        // remove_filter( 'wp_mail', array( $this->mailer, 'collect' ) );

        // Verification of switch_to_locale / restore_current_locale calls
        // This would typically require a test spy or mock on the global WordPress functions,
        // which is advanced and often framework-specific.
        // For conceptual purposes:
        // $this->assert_function_called('switch_to_locale');
        // $this->assert_function_called('restore_current_locale');
        $this->markTestIncomplete('Email capture and translation file setup needed. Advanced mocking for locale function calls.');
    }
}

?>
