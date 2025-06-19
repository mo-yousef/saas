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
        // 1. Test saving a valid currency code
        $valid_code = 'EUR';
        $this->settings_manager->update_setting($this->user_id, 'biz_currency_code', $valid_code);
        $retrieved_code = $this->settings_manager->get_setting($this->user_id, 'biz_currency_code');
        $this->assertEquals($valid_code, $retrieved_code, 'Failed to save and retrieve a valid currency code.');

        // 2. Test saving an invalid currency code (too long) - expecting sanitization
        $invalid_code_long = 'EURO';
        $this->settings_manager->update_setting($this->user_id, 'biz_currency_code', $invalid_code_long);
        $retrieved_code_long = $this->settings_manager->get_setting($this->user_id, 'biz_currency_code');
        // The sanitization logic is: preg_replace('/[^A-Z]/', '', strtoupper(substr(trim($value), 0, 3)));
        // Then, if strlen !== 3, it defaults. 'EURO' -> 'EUR' (which is valid, length 3)
        $this->assertEquals('EUR', $retrieved_code_long, 'Failed to sanitize a long currency code correctly.');

        // 3. Test saving an invalid currency code (lowercase) - expecting sanitization
        $invalid_code_lower = 'gbp';
        $this->settings_manager->update_setting($this->user_id, 'biz_currency_code', $invalid_code_lower);
        $retrieved_code_lower = $this->settings_manager->get_setting($this->user_id, 'biz_currency_code');
        $this->assertEquals('GBP', $retrieved_code_lower, 'Failed to sanitize a lowercase currency code to uppercase.');

        // 4. Test saving an invalid currency code (too short) - expecting default
        $invalid_code_short = 'EU';
        $this->settings_manager->update_setting($this->user_id, 'biz_currency_code', $invalid_code_short);
        $retrieved_code_short = $this->settings_manager->get_setting($this->user_id, 'biz_currency_code');
        $this->assertEquals(self::$default_currency_code, $retrieved_code_short, 'Failed to default an overly short currency code.');

        // 5. Test saving an invalid currency code (with numbers/symbols) - expecting sanitization then default if length mismatch
        $invalid_code_symbols = 'U$1';
        $this->settings_manager->update_setting($this->user_id, 'biz_currency_code', $invalid_code_symbols);
        $retrieved_code_symbols = $this->settings_manager->get_setting($this->user_id, 'biz_currency_code');
        // 'U$1' -> 'U' (substr(0,3) after preg_replace) -> strlen is 1, so defaults to USD
        $this->assertEquals(self::$default_currency_code, $retrieved_code_symbols, 'Failed to sanitize currency code with symbols and default.');

        // 6. Test retrieving with no setting (should return default)
        // To do this, we'd need to delete the setting or use a new user
        $new_user_id = $this->factory->user->create();
        $default_retrieved = $this->settings_manager->get_setting($new_user_id, 'biz_currency_code');
        $this->assertEquals(self::$default_currency_code, $default_retrieved, 'Should retrieve default currency code if none is set.');
    }

    /**
     * Test saving and retrieving biz_user_language.
     */
    public function test_save_and_get_biz_user_language() {
        // 1. Test saving a valid language code (en_US)
        $valid_lang_1 = 'en_US';
        $this->settings_manager->update_setting($this->user_id, 'biz_user_language', $valid_lang_1);
        $retrieved_lang_1 = $this->settings_manager->get_setting($this->user_id, 'biz_user_language');
        $this->assertEquals($valid_lang_1, $retrieved_lang_1, 'Failed to save and retrieve en_US.');

        // 2. Test saving another valid language code (es_ES)
        $valid_lang_2 = 'es_ES';
        $this->settings_manager->update_setting($this->user_id, 'biz_user_language', $valid_lang_2);
        $retrieved_lang_2 = $this->settings_manager->get_setting($this->user_id, 'biz_user_language');
        $this->assertEquals($valid_lang_2, $retrieved_lang_2, 'Failed to save and retrieve es_ES.');

        // 3. Test saving a valid short language code (fr) - assuming sanitization handles this by defaulting
        // Current sanitization: preg_match('/^[a-z]{2}_[A-Z]{2}$/', trim($value))
        // 'fr' will fail this regex and default.
        $valid_lang_short_but_incomplete = 'fr';
        $this->settings_manager->update_setting($this->user_id, 'biz_user_language', $valid_lang_short_but_incomplete);
        $retrieved_lang_short = $this->settings_manager->get_setting($this->user_id, 'biz_user_language');
        $this->assertEquals(self::$default_user_language, $retrieved_lang_short, 'Short language code "fr" should default as it does not match xx_XX.');

        // 4. Test saving an invalid format (e.g., en-GB instead of en_GB) - expecting default
        $invalid_format_1 = 'en-GB';
        $this->settings_manager->update_setting($this->user_id, 'biz_user_language', $invalid_format_1);
        $retrieved_invalid_1 = $this->settings_manager->get_setting($this->user_id, 'biz_user_language');
        $this->assertEquals(self::$default_user_language, $retrieved_invalid_1, 'Invalid format en-GB should default.');

        // 5. Test saving an invalid too short code (e) - expecting default
        $invalid_format_2 = 'e';
        $this->settings_manager->update_setting($this->user_id, 'biz_user_language', $invalid_format_2);
        $retrieved_invalid_2 = $this->settings_manager->get_setting($this->user_id, 'biz_user_language');
        $this->assertEquals(self::$default_user_language, $retrieved_invalid_2, 'Invalid short code "e" should default.');

        // 6. Test saving an invalid code with numbers (en_U1) - expecting default
        $invalid_format_3 = 'en_U1';
        $this->settings_manager->update_setting($this->user_id, 'biz_user_language', $invalid_format_3);
        $retrieved_invalid_3 = $this->settings_manager->get_setting($this->user_id, 'biz_user_language');
        $this->assertEquals(self::$default_user_language, $retrieved_invalid_3, 'Invalid code with numbers en_U1 should default.');

        // 7. Test retrieving with no setting (should return default)
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
     * E.g., languages/en_US.mo and languages/es_ES.mo
     * With a test string like: 'Booking Summary:' translated differently.
     */
    public function test_language_switching_for_notifications() {
        // Assume en_US.mo has "Booking Summary:"
        // Assume es_ES.mo has "Resumen de Reserva:" for "Booking Summary:"

        // $this->mailer = new \WPMailCollector();
        // add_filter( 'wp_mail', array( $this->mailer, 'collect' ) );

        $notifications = new Notifications();
        $spanish_lang_code = 'es_ES';
        $english_lang_code = 'en_US';

        // Set user's language to Spanish
        $this->settings_manager->update_setting($this->user_id, 'biz_user_language', $spanish_lang_code);

        $booking_details = [
            'booking_reference' => 'TESTLANG123', 'service_names' => 'Servicio de Prueba',
            'booking_date_time' => '2023-01-01 10:00', 'total_price' => 50,
            'customer_name' => 'Juan Perez', 'service_address' => 'Calle Falsa 123'
        ];
        $customer_email = 'juan@example.com';

        // --- Test Customer Confirmation in Spanish ---
        // if (method_exists($this->mailer, 'clear_emails')) $this->mailer->clear_emails();
        $notifications->send_booking_confirmation_customer($booking_details, $customer_email, $this->user_id);
        // $sent_email_es = $this->mailer->get_last_email();
        // $this->assertNotNull($sent_email_es, "Spanish customer email not sent/captured.");
        // $this->assertStringContainsString("Resumen de Reserva:", $sent_email_es['body'], "Email body not in Spanish.");
        // $this->assertStringNotContainsString("Booking Summary:", $sent_email_es['body'], "Email body contains English string when Spanish expected.");

        // --- Test Admin Confirmation in Spanish (as tenant is the admin) ---
        // if (method_exists($this->mailer, 'clear_emails')) $this->mailer->clear_emails();
        $admin_booking_details = array_merge($booking_details, ['customer_email' => $customer_email, 'customer_phone' => 'N/A']);
        $notifications->send_booking_confirmation_admin($admin_booking_details, $this->user_id);
        // $sent_admin_email_es = $this->mailer->get_last_email();
        // $this->assertNotNull($sent_admin_email_es, "Spanish admin email not sent/captured.");
        // $this->assertStringContainsString("Resumen de Reserva:", $sent_admin_email_es['body'], "Admin email body not in Spanish.");


        // Switch user's language back to English (or site default) and test again
        $this->settings_manager->update_setting($this->user_id, 'biz_user_language', $english_lang_code);
        // if (method_exists($this->mailer, 'clear_emails')) $this->mailer->clear_emails();
        $notifications->send_booking_confirmation_customer($booking_details, $customer_email, $this->user_id);
        // $sent_email_en = $this->mailer->get_last_email();
        // $this->assertNotNull($sent_email_en, "English customer email not sent/captured.");
        // $this->assertStringContainsString("Booking Summary:", $sent_email_en['body'], "Email body not in English after locale switch back.");
        // $this->assertStringNotContainsString("Resumen de Reserva:", $sent_email_en['body'], "Email body contains Spanish string when English expected.");

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
