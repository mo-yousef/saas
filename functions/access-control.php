<?php
/**
 * Access Control System for NORDBOOKING
 * Handles trial expiration and subscription-based access control
 * @package NORDBOOKING
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Check if user has access to dashboard based on subscription status
 */
function nordbooking_check_dashboard_access() {
    // Skip access control for admin users and non-dashboard pages
    if (current_user_can('administrator') || !is_user_logged_in()) {
        return true;
    }
    
    // Only apply access control to dashboard pages
    $current_url = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($current_url, '/dashboard/') === false) {
        return true;
    }
    
    // Allow access to subscription page always (so users can upgrade)
    if (strpos($current_url, '/dashboard/subscription/') !== false) {
        return true;
    }
    

    
    $user_id = get_current_user_id();
    
    // Check subscription status
    if (class_exists('\NORDBOOKING\Classes\Subscription')) {
        $status = \NORDBOOKING\Classes\Subscription::get_subscription_status($user_id);
        
        // Block access for expired users (both trial and subscription)
        if (in_array($status, ['expired_trial', 'expired'])) {
            return false;
        }
        
        // Allow access for active subscribers and trial users
        if (in_array($status, ['active', 'trial'])) {
            return true;
        }
        
        // Block access for cancelled subscriptions that have ended
        if ($status === 'cancelled') {
            // Check if cancelled subscription still has access time remaining
            $days_left = \NORDBOOKING\Classes\Subscription::get_days_until_next_payment($user_id);
            if ($days_left <= 0) {
                return false; // Access has ended
            }
            return true; // Still has access until period end
        }
    }
    
    return true; // Default to allow access
}

/**
 * Check if user's subscription has expired (for UI modifications)
 */
function nordbooking_is_subscription_expired($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id || current_user_can('administrator')) {
        return false;
    }
    
    if (class_exists('\NORDBOOKING\Classes\Subscription')) {
        $status = \NORDBOOKING\Classes\Subscription::get_subscription_status($user_id);
        
        // Consider expired if trial expired or subscription expired
        if (in_array($status, ['expired_trial', 'expired'])) {
            return true;
        }
        
        // Check cancelled subscriptions that have ended
        if ($status === 'cancelled') {
            $days_left = \NORDBOOKING\Classes\Subscription::get_days_until_next_payment($user_id);
            return ($days_left <= 0);
        }
    }
    
    return false;
}

/**
 * Redirect users with expired trials to subscription page
 */
function nordbooking_redirect_expired_users() {
    if (!nordbooking_check_dashboard_access()) {
        $subscription_url = home_url('/dashboard/subscription/?expired=1');
        wp_redirect($subscription_url);
        exit;
    }
}

// Hook into template redirect to check access
add_action('template_redirect', 'nordbooking_redirect_expired_users');

/**
 * Lock registration form for expired trial users
 */
function nordbooking_check_registration_access() {
    if (!is_user_logged_in()) {
        return true; // Allow new users to register
    }
    
    $user_id = get_current_user_id();
    
    if (class_exists('\NORDBOOKING\Classes\Subscription')) {
        $status = \NORDBOOKING\Classes\Subscription::get_subscription_status($user_id);
        
        // Block registration form access for expired trial users
        if ($status === 'expired_trial') {
            return false;
        }
    }
    
    return true;
}

/**
 * Add access control to registration page
 */
function nordbooking_registration_access_control() {
    // Only apply to registration page
    if (!is_page_template('page-register.php') && !is_page('register')) {
        return;
    }
    
    if (!nordbooking_check_registration_access()) {
        $subscription_url = home_url('/dashboard/subscription/?expired=1');
        wp_redirect($subscription_url);
        exit;
    }
}

add_action('template_redirect', 'nordbooking_registration_access_control');

/**
 * Send welcome email after successful subscription activation
 */
function nordbooking_send_subscription_welcome_email($user_id) {
    $user = get_userdata($user_id);
    if (!$user) {
        return;
    }
    
    $user_email = $user->user_email;
    $user_name = $user->display_name;
    $site_name = get_bloginfo('name');
    
    $subject = sprintf('[%s] Congratulations! Your plan subscription is active', $site_name);
    
    $message = sprintf(
        "Dear %s,\n\n" .
        "Congratulations! Your Pro Plan subscription is now active.\n\n" .
        "You now have full access to all features:\n" .
        "• Unlimited bookings\n" .
        "• Advanced calendar management\n" .
        "• Payment processing\n" .
        "• Customer portal\n" .
        "• Team management\n" .
        "• Analytics & reporting\n" .
        "• Priority support\n\n" .
        "Access your dashboard: %s\n\n" .
        "Thank you for choosing %s!\n\n" .
        "Best regards,\n" .
        "The %s Team",
        $user_name,
        home_url('/dashboard/'),
        $site_name,
        $site_name
    );
    
    // Send email
    wp_mail($user_email, $subject, $message);
    
    // Log the email sending
    error_log("NORDBOOKING: Welcome email sent to user {$user_id} ({$user_email})");
}

/**
 * Handle subscription activation and send welcome email
 */
function nordbooking_handle_subscription_activation($user_id) {
    if (!$user_id) {
        return;
    }
    
    // Check if this is a new activation (not a renewal)
    $activation_sent = get_user_meta($user_id, 'nordbooking_welcome_email_sent', true);
    
    if (!$activation_sent) {
        // Send welcome email
        nordbooking_send_subscription_welcome_email($user_id);
        
        // Mark as sent to avoid duplicate emails
        update_user_meta($user_id, 'nordbooking_welcome_email_sent', time());
    }
}

/**
 * AJAX handler for cancelling trial/subscription
 */
add_action('wp_ajax_nordbooking_cancel_trial', 'nordbooking_handle_cancel_trial');

function nordbooking_handle_cancel_trial() {
    // Security check
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'nordbooking_dashboard_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed.'), 403);
        return;
    }
    
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(array('message' => 'User not authenticated.'), 401);
        return;
    }
    
    try {
        if (class_exists('\NORDBOOKING\Classes\Subscription')) {
            $status = \NORDBOOKING\Classes\Subscription::get_subscription_status($user_id);
            
            if ($status === 'trial') {
                // For trial users, immediately expire the trial
                $subscription = \NORDBOOKING\Classes\Subscription::get_subscription($user_id);
                if ($subscription) {
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'nordbooking_subscriptions';
                    
                    $wpdb->update(
                        $table_name,
                        array(
                            'status' => 'expired_trial',
                            'trial_ends_at' => current_time('mysql'),
                            'updated_at' => current_time('mysql')
                        ),
                        array('user_id' => $user_id),
                        array('%s', '%s', '%s'),
                        array('%d')
                    );
                    
                    wp_send_json_success(array(
                        'message' => 'Trial cancelled successfully. You can reactivate anytime by subscribing.',
                        'redirect_url' => home_url('/dashboard/subscription/?expired=1')
                    ));
                } else {
                    wp_send_json_error(array('message' => 'No trial subscription found.'), 400);
                }
            } else {
                wp_send_json_error(array('message' => 'Only trial subscriptions can be cancelled this way.'), 400);
            }
        } else {
            wp_send_json_error(array('message' => 'Subscription system not available.'), 500);
        }
    } catch (Exception $e) {
        error_log('Trial cancellation error: ' . $e->getMessage());
        wp_send_json_error(array('message' => 'Failed to cancel trial. Please try again.'), 500);
    }
}

/**
 * Enhanced AJAX handler for cancelling active subscriptions
 */
add_action('wp_ajax_nordbooking_cancel_subscription', 'nordbooking_handle_cancel_subscription');

function nordbooking_handle_cancel_subscription() {
    // Security check
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'nordbooking_dashboard_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed.'), 403);
        return;
    }
    
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(array('message' => 'User not authenticated.'), 401);
        return;
    }
    
    try {
        if (class_exists('\NORDBOOKING\Classes\Subscription')) {
            $status = \NORDBOOKING\Classes\Subscription::get_subscription_status($user_id);
            
            if ($status === 'active') {
                $subscription = \NORDBOOKING\Classes\Subscription::get_subscription($user_id);
                if ($subscription && !empty($subscription['stripe_subscription_id'])) {
                    
                    // Cancel the subscription in Stripe
                    if (class_exists('\NORDBOOKING\Classes\StripeConfig')) {
                        $stripe_config = new \NORDBOOKING\Classes\StripeConfig();
                        $stripe = $stripe_config->get_stripe_instance();
                        
                        if ($stripe) {
                            try {
                                // Cancel the subscription at period end
                                $stripe_subscription = $stripe->subscriptions->update(
                                    $subscription['stripe_subscription_id'],
                                    ['cancel_at_period_end' => true]
                                );
                                
                                // Update local database
                                global $wpdb;
                                $table_name = $wpdb->prefix . 'nordbooking_subscriptions';
                                
                                $wpdb->update(
                                    $table_name,
                                    array(
                                        'status' => 'cancelled',
                                        'updated_at' => current_time('mysql')
                                    ),
                                    array('user_id' => $user_id),
                                    array('%s', '%s'),
                                    array('%d')
                                );
                                
                                wp_send_json_success(array(
                                    'message' => 'Your subscription has been cancelled successfully. You will continue to have access until the end of your current billing period.',
                                    'status' => 'cancelled'
                                ));
                                
                            } catch (Exception $stripe_error) {
                                error_log('Stripe cancellation error: ' . $stripe_error->getMessage());
                                wp_send_json_error(array('message' => 'Failed to cancel subscription with payment processor. Please try again or contact support.'), 500);
                            }
                        } else {
                            wp_send_json_error(array('message' => 'Payment processor not available.'), 500);
                        }
                    } else {
                        wp_send_json_error(array('message' => 'Stripe configuration not available.'), 500);
                    }
                } else {
                    wp_send_json_error(array('message' => 'No active subscription found.'), 400);
                }
            } else {
                wp_send_json_error(array('message' => 'Only active subscriptions can be cancelled.'), 400);
            }
        } else {
            wp_send_json_error(array('message' => 'Subscription system not available.'), 500);
        }
    } catch (Exception $e) {
        error_log('Subscription cancellation error: ' . $e->getMessage());
        wp_send_json_error(array('message' => 'Failed to cancel subscription. Please try again.'), 500);
    }
}

/**
 * Check if user should receive welcome email after payment
 */
function nordbooking_check_welcome_email_on_success() {
    if (isset($_GET['success']) && $_GET['success'] == '1') {
        $user_id = get_current_user_id();
        if ($user_id) {
            // Sync subscription status first
            if (class_exists('\NORDBOOKING\Classes\Subscription')) {
                \NORDBOOKING\Classes\Subscription::sync_subscription_status($user_id);
                $status = \NORDBOOKING\Classes\Subscription::get_subscription_status($user_id);
                
                // If subscription is now active, handle activation
                if ($status === 'active') {
                    nordbooking_handle_subscription_activation($user_id);
                }
            }
        }
    }
}

add_action('wp', 'nordbooking_check_welcome_email_on_success');

/**
 * Send welcome email for new user registration
 */
function nordbooking_send_registration_welcome_email($user_id) {
    $user = get_userdata($user_id);
    if (!$user) {
        return;
    }
    
    $user_email = $user->user_email;
    $user_name = $user->display_name;
    $site_name = get_bloginfo('name');
    
    $subject = sprintf('[%s] Welcome! Your account has been created', $site_name);
    
    $message = sprintf(
        "Dear %s,\n\n" .
        "Welcome to %s! Your account has been successfully created.\n\n" .
        "You now have access to your 7-day free trial with full Pro features:\n" .
        "• Unlimited bookings\n" .
        "• Advanced calendar management\n" .
        "• Payment processing\n" .
        "• Customer portal\n" .
        "• Team management\n" .
        "• Analytics & reporting\n" .
        "• Priority support\n\n" .
        "Access your dashboard: %s\n\n" .
        "Your free trial will automatically expire in 7 days. You can upgrade to the Pro plan at any time to continue using all features.\n\n" .
        "If you have any questions, please don't hesitate to contact our support team.\n\n" .
        "Best regards,\n" .
        "The %s Team",
        $user_name,
        $site_name,
        home_url('/dashboard/'),
        $site_name
    );
    
    // Send email
    wp_mail($user_email, $subject, $message);
    
    // Log the email sending
    error_log("NORDBOOKING: Registration welcome email sent to user {$user_id} ({$user_email})");
}

/**
 * Check if booking form should be disabled for expired users
 */
function nordbooking_is_booking_form_disabled($user_id = null) {
    if (!$user_id) {
        // For public booking forms, get the business owner from the form
        $tenant_id = isset($_GET['tenant_id']) ? intval($_GET['tenant_id']) : 0;
        if ($tenant_id) {
            $user_id = $tenant_id;
        } else {
            return false; // Can't determine, allow access
        }
    }
    
    return nordbooking_is_subscription_expired($user_id);
}

/**
 * Get expired subscription message for booking forms
 */
function nordbooking_get_booking_form_expired_message() {
    return __('This service is temporarily unavailable. The business owner\'s plan has expired. Please contact them directly or try again later.', 'NORDBOOKING');
}

/**
 * AJAX handler to check if booking form should be disabled
 */
add_action('wp_ajax_nordbooking_check_booking_form_status', 'nordbooking_check_booking_form_status');
add_action('wp_ajax_nopriv_nordbooking_check_booking_form_status', 'nordbooking_check_booking_form_status');

function nordbooking_check_booking_form_status() {
    $tenant_id = isset($_POST['tenant_id']) ? intval($_POST['tenant_id']) : 0;
    
    if (!$tenant_id) {
        wp_send_json_error(array('message' => 'Invalid tenant ID.'), 400);
        return;
    }
    
    $is_expired = nordbooking_is_subscription_expired($tenant_id);
    
    if ($is_expired) {
        wp_send_json_success(array(
            'disabled' => true,
            'message' => nordbooking_get_booking_form_expired_message()
        ));
    } else {
        wp_send_json_success(array(
            'disabled' => false,
            'message' => ''
        ));
    }
}