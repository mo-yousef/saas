<?php

use MoBooking\Classes\Auth;

/**
 * Class Test_MoBooking_Auth
 *
 * @group mobooking_auth
 */
class Test_MoBooking_Auth extends WP_UnitTestCase {

    protected $auth_instance;
    protected $business_owner_id;
    protected $worker_manager_id;
    protected $worker_staff_id;
    protected $worker_viewer_id;
    protected $unrelated_user_id;

    public function setUp(): void {
        parent::setUp();
        $this->auth_instance = new Auth();

        // It's important that roles are added before users are created with those roles.
        // WordPress's testing suite usually resets roles between tests, but explicit add is safer.
        Auth::add_business_owner_role();
        Auth::add_worker_roles();

        $this->business_owner_id = $this->factory->user->create( [ 'role' => Auth::ROLE_BUSINESS_OWNER ] );
        $this->worker_manager_id = $this->factory->user->create( [ 'role' => Auth::ROLE_WORKER_MANAGER ] );
        $this->worker_staff_id = $this->factory->user->create( [ 'role' => Auth::ROLE_WORKER_STAFF ] );
        $this->worker_viewer_id = $this->factory->user->create( [ 'role' => Auth::ROLE_WORKER_VIEWER ] );
        $this->unrelated_user_id = $this->factory->user->create( [ 'role' => 'subscriber' ] );

        // Associate workers with the business owner for some tests
        update_user_meta( $this->worker_manager_id, Auth::META_KEY_OWNER_ID, $this->business_owner_id );
        update_user_meta( $this->worker_staff_id, Auth::META_KEY_OWNER_ID, $this->business_owner_id );
        update_user_meta( $this->worker_viewer_id, Auth::META_KEY_OWNER_ID, $this->business_owner_id );
    }

    public function tearDown(): void {
        parent::tearDown();
        // WordPress test suite usually handles user deletion.
        // Roles might need to be removed if they interfere with other tests, but usually okay.
        // Auth::remove_worker_roles();
        // Auth::remove_business_owner_role();
    }

    /**
     * Test if roles are added correctly.
     */
    public function test_roles_are_added() {
        $this->assertNotNull( get_role( Auth::ROLE_BUSINESS_OWNER ), 'Business Owner role should exist.' );
        $this->assertNotNull( get_role( Auth::ROLE_WORKER_MANAGER ), 'Worker Manager role should exist.' );
        $this->assertNotNull( get_role( Auth::ROLE_WORKER_STAFF ), 'Worker Staff role should exist.' );
        $this->assertNotNull( get_role( Auth::ROLE_WORKER_VIEWER ), 'Worker Viewer role should exist.' );
    }

    /**
     * Test capabilities for Business Owner.
     */
    public function test_business_owner_capabilities() {
        $user = get_userdata( $this->business_owner_id );
        $this->assertTrue( $user->has_cap( Auth::ACCESS_MOBOOKING_DASHBOARD ) );
        $this->assertTrue( $user->has_cap( Auth::CAP_MANAGE_WORKERS ) );
        $this->assertTrue( $user->has_cap( Auth::CAP_MANAGE_BOOKINGS ) );
        $this->assertTrue( $user->has_cap( Auth::CAP_MANAGE_SERVICES ) );
        $this->assertTrue( $user->has_cap( Auth::CAP_MANAGE_BUSINESS_SETTINGS ) );
        // Add more capability checks as defined in add_business_owner_role
    }

    /**
     * Test capabilities for Worker Manager.
     */
    public function test_worker_manager_capabilities() {
        $user = get_userdata( $this->worker_manager_id );
        $this->assertTrue( $user->has_cap( Auth::ACCESS_MOBOOKING_DASHBOARD ) );
        $this->assertTrue( $user->has_cap( Auth::CAP_MANAGE_BOOKINGS ) );
        $this->assertTrue( $user->has_cap( Auth::CAP_MANAGE_SERVICES ) );
        $this->assertFalse( $user->has_cap( Auth::CAP_MANAGE_WORKERS ), 'Worker Manager should not manage other workers.' );
        $this->assertFalse( $user->has_cap( Auth::CAP_MANAGE_BUSINESS_SETTINGS ), 'Worker Manager should not manage business settings.' );
    }

    /**
     * Test capabilities for Worker Staff.
     */
    public function test_worker_staff_capabilities() {
        $user = get_userdata( $this->worker_staff_id );
        $this->assertTrue( $user->has_cap( Auth::ACCESS_MOBOOKING_DASHBOARD ) );
        $this->assertTrue( $user->has_cap( Auth::CAP_MANAGE_BOOKINGS ) ); // As per current setup
        $this->assertTrue( $user->has_cap( Auth::CAP_VIEW_SERVICES ) );
        $this->assertFalse( $user->has_cap( Auth::CAP_MANAGE_SERVICES ) );
        $this->assertFalse( $user->has_cap( Auth::CAP_MANAGE_WORKERS ) );
    }

    /**
     * Test capabilities for Worker Viewer.
     */
    public function test_worker_viewer_capabilities() {
        $user = get_userdata( $this->worker_viewer_id );
        $this->assertTrue( $user->has_cap( Auth::ACCESS_MOBOOKING_DASHBOARD ) );
        $this->assertTrue( $user->has_cap( Auth::CAP_VIEW_BOOKINGS ) );
        $this->assertFalse( $user->has_cap( Auth::CAP_MANAGE_BOOKINGS ) );
        $this->assertFalse( $user->has_cap( Auth::CAP_MANAGE_WORKERS ) );
    }

    /**
     * Test Auth::get_business_owner_id_for_worker()
     */
    public function test_get_business_owner_id_for_worker() {
        $retrieved_owner_id = Auth::get_business_owner_id_for_worker( $this->worker_manager_id );
        $this->assertEquals( $this->business_owner_id, $retrieved_owner_id );

        $retrieved_owner_id_for_owner = Auth::get_business_owner_id_for_worker( $this->business_owner_id );
        $this->assertEmpty( $retrieved_owner_id_for_owner, "Should return empty or 0 if user is not a worker with the meta key." );
    }

    /**
     * Test Auth::is_user_worker()
     */
    public function test_is_user_worker() {
        $this->assertTrue( Auth::is_user_worker( $this->worker_manager_id ) );
        $this->assertTrue( Auth::is_user_worker( $this->worker_staff_id ) );
        $this->assertTrue( Auth::is_user_worker( $this->worker_viewer_id ) );
        $this->assertFalse( Auth::is_user_worker( $this->business_owner_id ) );
        $this->assertFalse( Auth::is_user_worker( $this->unrelated_user_id ) );
    }

    /**
     * Test Auth::is_user_business_owner()
     */
    public function test_is_user_business_owner() {
        $this->assertTrue( Auth::is_user_business_owner( $this->business_owner_id ) );
        $this->assertFalse( Auth::is_user_business_owner( $this->worker_manager_id ) );
        $this->assertFalse( Auth::is_user_business_owner( $this->unrelated_user_id ) );
    }

    /**
     * Test handle_ajax_change_worker_role - core logic.
     * This test focuses on the role change logic rather than full AJAX simulation.
     */
    public function test_handle_ajax_change_worker_role_logic() {
        wp_set_current_user( $this->business_owner_id );

        // Worker starts as Manager
        $worker_user = get_userdata($this->worker_manager_id);
        $this->assertTrue(in_array(Auth::ROLE_WORKER_MANAGER, $worker_user->roles));

        // Prepare mocked $_POST data
        $_POST['worker_user_id'] = $this->worker_manager_id;
        $_POST['new_role'] = Auth::ROLE_WORKER_STAFF;
        // Nonce generation for direct testing can be tricky. We'll create one that passes.
        $_POST['mobooking_change_role_nonce'] = wp_create_nonce( 'mobooking_change_worker_role_nonce_' . $this->worker_manager_id );

        // Capture output for JSON response check (optional, can be complex)
        // ob_start();
        // try {
        //     $this->auth_instance->handle_ajax_change_worker_role();
        // } catch (WPAjaxDieContinueException $e) {
        //     // Expected behavior for wp_send_json_success
        // }
        // $json_response = ob_get_clean();

        // For now, directly call and check user role
        $this->auth_instance->handle_ajax_change_worker_role(); // This will call wp_send_json_* and exit.

        // To properly test after this, we need to use @runInSeparateProcess and expectOutputString for wp_send_json
        // Or, refactor handle_ajax_change_worker_role to return data instead of echoing for easier testing.
        // For this subtask, we'll assert the role change directly as a simplified check.

        // Re-fetch worker user data
        $updated_worker_user = get_userdata( $this->worker_manager_id );
        $this->assertFalse( in_array( Auth::ROLE_WORKER_MANAGER, $updated_worker_user->roles ), 'Old role (Manager) should be removed.' );
        $this->assertTrue( in_array( Auth::ROLE_WORKER_STAFF, $updated_worker_user->roles ), 'New role (Staff) should be added.' );

        // Clean up $_POST
        unset($_POST['worker_user_id']);
        unset($_POST['new_role']);
        unset($_POST['mobooking_change_role_nonce']);
    }

    /**
     * Test handle_ajax_revoke_worker_access - core logic.
     */
    public function test_handle_ajax_revoke_worker_access_logic() {
        wp_set_current_user( $this->business_owner_id );

        $worker_user_before = get_userdata($this->worker_staff_id);
        $this->assertTrue(in_array(Auth::ROLE_WORKER_STAFF, $worker_user_before->roles));
        $this->assertEquals($this->business_owner_id, get_user_meta($this->worker_staff_id, Auth::META_KEY_OWNER_ID, true));

        $_POST['worker_user_id'] = $this->worker_staff_id;
        $_POST['mobooking_revoke_access_nonce'] = wp_create_nonce( 'mobooking_revoke_worker_access_nonce_' . $this->worker_staff_id );

        // Similar to above, direct call for simplified check
        // @TODO: Improve AJAX testing to capture wp_send_json_* output properly.
        // For now, we might get 'headers already sent' warning if not run in separate process.
        try {
            $this->auth_instance->handle_ajax_revoke_worker_access();
        } catch (\WP_UnitTest_Exception $e) {
            // Expected exception due to wp_die()
            $this->assertStringContainsString('wp_die() was called', $e->getMessage());
        }


        $worker_user_after = get_userdata($this->worker_staff_id);
        $this->assertFalse(in_array(Auth::ROLE_WORKER_STAFF, $worker_user_after->roles), "Worker Staff role should be removed.");
        $this->assertFalse(Auth::is_user_worker($this->worker_staff_id), "User should no longer be a MoBooking worker.");
        $this->assertEmpty(get_user_meta($this->worker_staff_id, Auth::META_KEY_OWNER_ID, true), "Owner meta key should be deleted.");

        // Check if role defaulted to subscriber if no other roles
        if (count($worker_user_before->roles) == 1 && $worker_user_before->roles[0] == Auth::ROLE_WORKER_STAFF) {
             $this->assertTrue(in_array('subscriber', $worker_user_after->roles), "Worker should default to subscriber role.");
        }

        unset($_POST['worker_user_id']);
        unset($_POST['mobooking_revoke_access_nonce']);
    }

    /**
     * Helper to call AJAX handlers and get JSON response.
     * Note: This requires the test method using it to have `@runInSeparateProcess`.
     */
    private function call_ajax_handler($action_callable) {
        ob_start();
        try {
            call_user_func($action_callable);
        } catch (\WP_UnitTest_Exception $e) {
            // Expected due to wp_die() in wp_send_json_*
        }
        return json_decode(ob_get_clean(), true);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_handle_ajax_registration_success_business_owner() {
        $_POST = [
            'nonce'            => wp_create_nonce(Auth::REGISTER_NONCE_ACTION),
            'email'            => 'newowner@example.com',
            'password'         => 'password123',
            'password_confirm' => 'password123',
            'first_name'       => 'Test',
            'last_name'        => 'Owner',
            'company_name'     => 'Test Company Inc.',
        ];

        $response = $this->call_ajax_handler([$this->auth_instance, 'handle_ajax_registration']);

        $this->assertTrue($response['success'], "Registration failed. Response: " . print_r($response, true));
        $this->assertEquals('Registration successful! Redirecting to your dashboard...', $response['data']['message']);
        $this->assertEquals(home_url('/dashboard/'), $response['data']['redirect_url']);

        $user = get_user_by('email', 'newowner@example.com');
        $this->assertInstanceOf(WP_User::class, $user);
        $this->assertEquals('Test', $user->first_name);
        $this->assertEquals('Owner', $user->last_name);
        $this->assertEquals('Test Owner', $user->display_name); // Default display name logic
        $this->assertEquals('Test Company Inc.', get_user_meta($user->ID, 'mobooking_company_name', true));
        $this->assertTrue(in_array(Auth::ROLE_BUSINESS_OWNER, $user->roles));

        // Check for business slug in tenant_settings
        $settings_manager = new \MoBooking\Classes\Settings();
        $slug = $settings_manager->get_setting($user->ID, 'bf_business_slug');
        $this->assertEquals('test-company-inc', $slug); // Assuming sanitize_title behavior

        // Clean up
        wp_delete_user($user->ID);
        global $wpdb;
        $settings_table = \MoBooking\Classes\Database::get_table_name('tenant_settings');
        $wpdb->delete($settings_table, ['user_id' => $user->ID, 'setting_name' => 'bf_business_slug']);
        unset($_POST);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_handle_ajax_registration_slug_uniqueness() {
        // User 1
        $_POST = [
            'nonce'            => wp_create_nonce(Auth::REGISTER_NONCE_ACTION),
            'email'            => 'owner1@example.com',
            'password'         => 'password123',
            'password_confirm' => 'password123',
            'first_name'       => 'Owner',
            'last_name'        => 'One',
            'company_name'     => 'Unique Company',
        ];
        $this->call_ajax_handler([$this->auth_instance, 'handle_ajax_registration']);
        $user1 = get_user_by('email', 'owner1@example.com');
        $settings_manager = new \MoBooking\Classes\Settings();
        $slug1 = $settings_manager->get_setting($user1->ID, 'bf_business_slug');
        $this->assertEquals('unique-company', $slug1);

        // User 2 - same company name
        $_POST = [
            'nonce'            => wp_create_nonce(Auth::REGISTER_NONCE_ACTION),
            'email'            => 'owner2@example.com',
            'password'         => 'password123',
            'password_confirm' => 'password123',
            'first_name'       => 'Owner',
            'last_name'        => 'Two',
            'company_name'     => 'Unique Company',
        ];
        $this->call_ajax_handler([$this->auth_instance, 'handle_ajax_registration']);
        $user2 = get_user_by('email', 'owner2@example.com');
        $slug2 = $settings_manager->get_setting($user2->ID, 'bf_business_slug');
        $this->assertEquals('unique-company-2', $slug2);

        // User 3 - company name that would result in 'unique-company-2' if not for user2
        $_POST = [
            'nonce'            => wp_create_nonce(Auth::REGISTER_NONCE_ACTION),
            'email'            => 'owner3@example.com',
            'password'         => 'password123',
            'password_confirm' => 'password123',
            'first_name'       => 'Owner',
            'last_name'        => 'Three',
            'company_name'     => 'Unique Company 2', // sanitize_title makes this 'unique-company-2'
        ];
        $this->call_ajax_handler([$this->auth_instance, 'handle_ajax_registration']);
        $user3 = get_user_by('email', 'owner3@example.com');
        $slug3 = $settings_manager->get_setting($user3->ID, 'bf_business_slug');
        $this->assertEquals('unique-company-2-2', $slug3);


        // Clean up
        wp_delete_user($user1->ID);
        wp_delete_user($user2->ID);
        wp_delete_user($user3->ID);
        global $wpdb;
        $settings_table = \MoBooking\Classes\Database::get_table_name('tenant_settings');
        $wpdb->delete($settings_table, ['user_id' => $user1->ID]);
        $wpdb->delete($settings_table, ['user_id' => $user2->ID]);
        $wpdb->delete($settings_table, ['user_id' => $user3->ID]);
        unset($_POST);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_handle_ajax_registration_missing_fields() {
        $test_cases = [
            ['first_name', 'First name is required.'],
            ['last_name', 'Last name is required.'],
            ['email', 'A valid email address is required.'],
            ['password', 'Please enter a password.'],
            ['password_confirm', 'Passwords do not match.'],
            ['company_name', 'Company name is required for business registration.'],
        ];

        $base_data = [
            'nonce'            => wp_create_nonce(Auth::REGISTER_NONCE_ACTION),
            'first_name'       => 'Test',
            'last_name'        => 'User',
            'email'            => 'missingfields@example.com',
            'password'         => 'password123',
            'password_confirm' => 'password123',
            'company_name'     => 'Test Co',
        ];

        foreach ($test_cases as $case) {
            $field_to_remove = $case[0];
            $expected_message = $case[1];

            $_POST = $base_data;
            if ($field_to_remove === 'password_confirm') { // Specific case for password mismatch
                 $_POST['password_confirm'] = 'wrongpassword';
            } else {
                unset($_POST[$field_to_remove]);
                if($field_to_remove === 'email') $_POST['email'] = ''; // Test empty email
            }

            $response = $this->call_ajax_handler([$this->auth_instance, 'handle_ajax_registration']);
            $this->assertFalse($response['success'], "Test failed for missing/invalid field: {$field_to_remove}");
            $this->assertEquals($expected_message, $response['data']['message'], "Test failed for missing/invalid field: {$field_to_remove}");
        }
        unset($_POST);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_handle_ajax_registration_invited_worker() {
        $inviter = get_userdata($this->business_owner_id);
        $token = wp_generate_password(32, false);
        $invitation_option_key = 'mobooking_invitation_' . $token;
        $invitation_data = [
            'inviter_id'    => $this->business_owner_id,
            'worker_email'  => 'invitedworker@example.com',
            'assigned_role' => Auth::ROLE_WORKER_STAFF,
            'timestamp'     => time(),
        ];
        set_transient($invitation_option_key, $invitation_data, DAY_IN_SECONDS);

        $_POST = [
            'nonce'             => wp_create_nonce(Auth::REGISTER_NONCE_ACTION),
            'email'             => 'invitedworker@example.com',
            'password'          => 'password123',
            'password_confirm'  => 'password123',
            'first_name'        => 'Invited',
            'last_name'         => 'Worker',
            // company_name is not required for invited worker
            'inviter_id'        => $this->business_owner_id,
            'role_to_assign'    => Auth::ROLE_WORKER_STAFF,
            'invitation_token'  => $token,
        ];

        $response = $this->call_ajax_handler([$this->auth_instance, 'handle_ajax_registration']);

        $this->assertTrue($response['success'], "Invited worker registration failed. Response: " . print_r($response, true));
        $this->assertEquals('Registration successful! Redirecting to your dashboard...', $response['data']['message']);

        $user = get_user_by('email', 'invitedworker@example.com');
        $this->assertInstanceOf(WP_User::class, $user);
        $this->assertEquals('Invited', $user->first_name);
        $this->assertEquals('Worker', $user->last_name);
        $this->assertEquals('Invited Worker', $user->display_name); // Default display name logic
        $this->assertTrue(in_array(Auth::ROLE_WORKER_STAFF, $user->roles));
        $this->assertEquals($this->business_owner_id, get_user_meta($user->ID, Auth::META_KEY_OWNER_ID, true));

        // Company name and business slug should not be set for worker
        $this->assertEmpty(get_user_meta($user->ID, 'mobooking_company_name', true));
        $settings_manager = new \MoBooking\Classes\Settings();
        $this->assertEmpty($settings_manager->get_setting($user->ID, 'bf_business_slug'));

        // Check transient was deleted
        $this->assertFalse(get_transient($invitation_option_key));

        // Clean up
        wp_delete_user($user->ID);
        unset($_POST);
    }


    // Further tests could include:
    // - Attempting to change role of a worker not owned by current business owner (expect error)
    // - Attempting to revoke access for a worker not owned (expect error)
    // - Passing invalid nonces (requires more advanced AJAX test setup)

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_handle_send_password_reset_link_ajax_success() {
        $user_email = 'existinguser@example.com';
        $this->factory->user->create(['user_email' => $user_email, 'user_login' => 'existinguser']);

        $_POST = [
            'nonce'      => wp_create_nonce('mobooking_forgot_password_nonce_action'),
            'user_email' => $user_email,
        ];

        // Hook to capture wp_mail arguments
        $sent_mail_args = null;
        add_filter('wp_mail', function($args) use (&$sent_mail_args) {
            $sent_mail_args = $args;
            return $args; // Continue to allow email sending (though test suite usually prevents actual sending)
        });

        $response = $this->call_ajax_handler([$this->auth_instance, 'handle_send_password_reset_link_ajax']);

        $this->assertTrue($response['success']);
        $this->assertEquals('If an account with that email exists, a password reset link has been sent.', $response['data']['message']);

        $this->assertNotNull($sent_mail_args, 'wp_mail was not called.');
        $this->assertEquals($user_email, $sent_mail_args['to']);
        $this->assertStringContainsString('Password Reset', $sent_mail_args['subject']);
        $this->assertStringContainsString('wp-login.php?action=rp&key=', $sent_mail_args['message']);
        $this->assertStringContainsString('login=existinguser', $sent_mail_args['message']);

        // Check if reset key was stored (WordPress stores it in user_activation_key column)
        $user = get_user_by('email', $user_email);
        $this->assertNotEmpty($user->user_activation_key); // This is where WP stores the key temporarily

        unset($_POST);
        remove_all_filters('wp_mail');
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_handle_send_password_reset_link_ajax_non_existent_email() {
        $_POST = [
            'nonce'      => wp_create_nonce('mobooking_forgot_password_nonce_action'),
            'user_email' => 'nonexistent@example.com',
        ];

        $wp_mail_called = false;
        add_filter('wp_mail', function($args) use (&$wp_mail_called) {
            $wp_mail_called = true;
            return false; // Prevent email sending for this test
        });

        $response = $this->call_ajax_handler([$this->auth_instance, 'handle_send_password_reset_link_ajax']);

        $this->assertTrue($response['success']); // Should still be success to prevent enumeration
        $this->assertEquals('If an account with that email exists, a password reset link has been sent.', $response['data']['message']);
        $this->assertFalse($wp_mail_called, 'wp_mail should not have been called for a non-existent email.');

        unset($_POST);
        remove_all_filters('wp_mail');
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_handle_send_password_reset_link_ajax_invalid_email() {
        $_POST = [
            'nonce'      => wp_create_nonce('mobooking_forgot_password_nonce_action'),
            'user_email' => 'invalid-email',
        ];
        $response = $this->call_ajax_handler([$this->auth_instance, 'handle_send_password_reset_link_ajax']);
        $this->assertFalse($response['success']);
        $this->assertEquals('Please provide a valid email address.', $response['data']['message']);
        unset($_POST);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_handle_send_password_reset_link_ajax_missing_email() {
        $_POST = [
            'nonce'      => wp_create_nonce('mobooking_forgot_password_nonce_action'),
            // 'user_email' is missing
        ];
        $response = $this->call_ajax_handler([$this->auth_instance, 'handle_send_password_reset_link_ajax']);
        $this->assertFalse($response['success']);
        $this->assertEquals('Please provide a valid email address.', $response['data']['message']); // Or "Email not provided"
        unset($_POST);
    }

     /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_handle_send_password_reset_link_ajax_invalid_nonce() {
        $_POST = [
            'nonce'      => 'invalidnonce',
            'user_email' => 'test@example.com',
        ];
        // check_ajax_referer dies with a -1 or specific message if not caught.
        // The call_ajax_handler catches the WP_UnitTest_Exception from wp_die().
        // We expect the response to be null or an error structure if wp_die output something before exiting.
        // WordPress's default die message for bad nonce is often just "-1" or a specific string.
        // Since check_ajax_referer dies, the $response might not be a typical JSON error.
        // We might need to expect an exception or check the output buffer directly.
        // For simplicity, let's check if the output is NOT a success JSON.

        ob_start();
        try {
            $this->auth_instance->handle_send_password_reset_link_ajax();
        } catch (\WP_UnitTest_Exception $e) {
            // Expected
        }
        $output = ob_get_clean();

        // A failed nonce check usually results in wp_die('-1') or similar status code if headers not sent.
        // If headers are sent, it will output an error message.
        // The key is that it shouldn't be a JSON success response.
        $json_output = json_decode($output, true);
        $this->assertFalse(isset($json_output['success']) && $json_output['success'] === true);
        // More specific check if WP_DIE_AJAX_HANDLER is customized or for default WP behavior:
        // $this->assertEquals('-1', $output); // Or check for part of the die message.

        unset($_POST);
    }

}
?>
