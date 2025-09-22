<?php
/**
 * Enhanced Stripe Webhook Handler with Real-time Processing
 * This file should replace or enhance the existing stripe-webhook.php
 */

// Load WordPress
$wp_load_paths = [
    '../../../wp-load.php',
    '../../../../wp-load.php',
    '../../../../../wp-load.php'
];

$wp_loaded = false;
foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once($path);
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded) {
    http_response_code(500);
    exit('WordPress not loaded');
}

// Enhanced webhook handler with logging and error handling
class EnhancedStripeWebhookHandler {
    
    private $log_file;
    
    public function __construct() {
        $this->log_file = NORDBOOKING_THEME_DIR . 'logs/stripe-webhook.log';
        $this->ensure_log_directory();
    }
    
    private function ensure_log_directory() {
        $log_dir = dirname($this->log_file);
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }
    }
    
    private function log($message, $level = 'INFO') {
        $timestamp = current_time('Y-m-d H:i:s');
        $log_entry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        file_put_contents($this->log_file, $log_entry, FILE_APPEND | LOCK_EX);
        
        // Also log to WordPress error log for critical issues
        if ($level === 'ERROR') {
            error_log('[NORDBOOKING Webhook] ' . $message);
        }
    }
    
    public function handle() {
        $this->log('Webhook received from IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        
        if (!class_exists('\NORDBOOKING\Classes\StripeConfig')) {
            $this->log('StripeConfig class not found', 'ERROR');
            http_response_code(500);
            exit('Configuration error');
        }
        
        if (!\NORDBOOKING\Classes\StripeConfig::is_configured()) {
            $this->log('Stripe not configured', 'ERROR');
            http_response_code(400);
            exit('Stripe not configured');
        }
        
        try {
            \Stripe\Stripe::setApiKey(\NORDBOOKING\Classes\StripeConfig::get_secret_key());
            $endpoint_secret = \NORDBOOKING\Classes\StripeConfig::get_webhook_secret();
            
            $payload = @file_get_contents('php://input');
            $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
            
            $this->log('Processing webhook with signature: ' . substr($sig_header, 0, 20) . '...');
            
            $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
            
            $this->log('Event type: ' . $event->type . ', ID: ' . $event->id);
            
            // Process the event
            $result = $this->processEvent($event);
            
            if ($result) {
                $this->log('Event processed successfully');
                http_response_code(200);
                echo json_encode(['status' => 'success', 'event_id' => $event->id]);
            } else {
                $this->log('Event processing failed', 'ERROR');
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Processing failed']);
            }
            
        } catch (\UnexpectedValueException $e) {
            $this->log('Invalid payload: ' . $e->getMessage(), 'ERROR');
            http_response_code(400);
            exit('Invalid payload');
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            $this->log('Invalid signature: ' . $e->getMessage(), 'ERROR');
            http_response_code(400);
            exit('Invalid signature');
        } catch (\Exception $e) {
            $this->log('Unexpected error: ' . $e->getMessage(), 'ERROR');
            http_response_code(500);
            exit('Server error');
        }
    }
    
    private function processEvent($event) {
        switch ($event->type) {
            case 'checkout.session.completed':
                return $this->handleCheckoutCompleted($event->data->object);
                
            case 'invoice.payment_succeeded':
                return $this->handlePaymentSucceeded($event->data->object);
                
            case 'invoice.payment_failed':
                return $this->handlePaymentFailed($event->data->object);
                
            case 'customer.subscription.created':
                return $this->handleSubscriptionCreated($event->data->object);
                
            case 'customer.subscription.updated':
                return $this->handleSubscriptionUpdated($event->data->object);
                
            case 'customer.subscription.deleted':
                return $this->handleSubscriptionDeleted($event->data->object);
                
            case 'customer.subscription.trial_will_end':
                return $this->handleTrialWillEnd($event->data->object);
                
            default:
                $this->log('Unhandled event type: ' . $event->type, 'WARN');
                return true; // Return true for unhandled events to acknowledge receipt
        }
    }
    
    private function handleCheckoutCompleted($session) {
        $this->log('Processing checkout.session.completed for session: ' . $session->id);
        
        $user_id = $session->client_reference_id ?? $session->metadata->wordpress_user_id ?? null;
        
        if (!$user_id) {
            $this->log('No user ID found in checkout session', 'ERROR');
            return false;
        }
        
        if ($session->mode === 'subscription' && $session->subscription) {
            try {
                $stripe_subscription = \Stripe\Subscription::retrieve($session->subscription);
                $this->updateSubscriptionFromStripe($user_id, $stripe_subscription);
                
                // Send welcome email
                $this->sendWelcomeEmail($user_id);
                
                $this->log("Subscription created for user {$user_id}: {$stripe_subscription->id}");
                return true;
            } catch (\Exception $e) {
                $this->log('Failed to retrieve subscription: ' . $e->getMessage(), 'ERROR');
                return false;
            }
        }
        
        return true;
    }
    
    private function handlePaymentSucceeded($invoice) {
        $this->log('Processing invoice.payment_succeeded for invoice: ' . $invoice->id);
        
        if ($invoice->subscription) {
            try {
                $stripe_subscription = \Stripe\Subscription::retrieve($invoice->subscription);
                $user_id = $this->getUserIdByStripeSubscription($stripe_subscription->id);
                
                if ($user_id) {
                    $this->updateSubscriptionFromStripe($user_id, $stripe_subscription);
                    $this->log("Payment succeeded for user {$user_id}, subscription {$stripe_subscription->id}");
                    
                    // Send payment confirmation email
                    $this->sendPaymentConfirmationEmail($user_id, $invoice);
                    
                    return true;
                }
            } catch (\Exception $e) {
                $this->log('Failed to handle payment succeeded: ' . $e->getMessage(), 'ERROR');
                return false;
            }
        }
        
        return true;
    }
    
    private function handlePaymentFailed($invoice) {
        $this->log('Processing invoice.payment_failed for invoice: ' . $invoice->id);
        
        if ($invoice->subscription) {
            try {
                $stripe_subscription = \Stripe\Subscription::retrieve($invoice->subscription);
                $user_id = $this->getUserIdByStripeSubscription($stripe_subscription->id);
                
                if ($user_id) {
                    // Update subscription status
                    $this->updateSubscriptionFromStripe($user_id, $stripe_subscription);
                    
                    // Send payment failed notification
                    $this->sendPaymentFailedEmail($user_id, $invoice);
                    
                    $this->log("Payment failed for user {$user_id}, subscription {$stripe_subscription->id}");
                    return true;
                }
            } catch (\Exception $e) {
                $this->log('Failed to handle payment failed: ' . $e->getMessage(), 'ERROR');
                return false;
            }
        }
        
        return true;
    }
    
    private function handleSubscriptionCreated($stripe_subscription) {
        $this->log('Processing customer.subscription.created for subscription: ' . $stripe_subscription->id);
        
        // Try to find user by customer ID
        $user_id = $this->getUserIdByStripeCustomer($stripe_subscription->customer);
        
        if ($user_id) {
            $this->updateSubscriptionFromStripe($user_id, $stripe_subscription);
            $this->log("Subscription created for user {$user_id}: {$stripe_subscription->id}");
            return true;
        }
        
        $this->log('No user found for customer: ' . $stripe_subscription->customer, 'WARN');
        return true;
    }
    
    private function handleSubscriptionUpdated($stripe_subscription) {
        $this->log('Processing customer.subscription.updated for subscription: ' . $stripe_subscription->id);
        
        $user_id = $this->getUserIdByStripeSubscription($stripe_subscription->id);
        
        if ($user_id) {
            $this->updateSubscriptionFromStripe($user_id, $stripe_subscription);
            $this->log("Subscription updated for user {$user_id}: {$stripe_subscription->id}");
            return true;
        }
        
        $this->log('No user found for subscription: ' . $stripe_subscription->id, 'WARN');
        return true;
    }
    
    private function handleSubscriptionDeleted($stripe_subscription) {
        $this->log('Processing customer.subscription.deleted for subscription: ' . $stripe_subscription->id);
        
        $user_id = $this->getUserIdByStripeSubscription($stripe_subscription->id);
        
        if ($user_id) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'nordbooking_subscriptions';
            
            $wpdb->update(
                $table_name,
                [
                    'status' => 'cancelled',
                    'ends_at' => date('Y-m-d H:i:s', $stripe_subscription->current_period_end),
                    'updated_at' => current_time('mysql')
                ],
                ['user_id' => $user_id]
            );
            
            // Send cancellation confirmation email
            $this->sendCancellationEmail($user_id);
            
            $this->log("Subscription cancelled for user {$user_id}: {$stripe_subscription->id}");
            return true;
        }
        
        return true;
    }
    
    private function handleTrialWillEnd($stripe_subscription) {
        $this->log('Processing customer.subscription.trial_will_end for subscription: ' . $stripe_subscription->id);
        
        $user_id = $this->getUserIdByStripeSubscription($stripe_subscription->id);
        
        if ($user_id) {
            // Send trial ending notification
            $this->sendTrialEndingEmail($user_id);
            $this->log("Trial ending notification sent for user {$user_id}");
            return true;
        }
        
        return true;
    }
    
    private function updateSubscriptionFromStripe($user_id, $stripe_subscription) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'nordbooking_subscriptions';
        
        $status = $this->mapStripeStatus($stripe_subscription->status);
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
        
        $result = $wpdb->update(
            $table_name,
            $update_data,
            ['user_id' => $user_id]
        );
        
        if ($result === false) {
            $this->log("Failed to update subscription for user {$user_id}: " . $wpdb->last_error, 'ERROR');
        }
        
        // Clear cache
        delete_transient("nordbooking_subscription_{$user_id}");
    }
    
    private function mapStripeStatus($stripe_status) {
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
    
    private function getUserIdByStripeSubscription($stripe_subscription_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'nordbooking_subscriptions';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM $table_name WHERE stripe_subscription_id = %s",
            $stripe_subscription_id
        ));
    }
    
    private function getUserIdByStripeCustomer($stripe_customer_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'nordbooking_subscriptions';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM $table_name WHERE stripe_customer_id = %s",
            $stripe_customer_id
        ));
    }
    
    private function sendWelcomeEmail($user_id) {
        $user = get_userdata($user_id);
        if (!$user) return;
        
        $subject = 'Welcome to NORDBOOKING Pro!';
        $message = "Hi {$user->display_name},\n\nWelcome to NORDBOOKING Pro! Your subscription is now active.\n\nYou can manage your subscription at: " . home_url('/dashboard/subscription/') . "\n\nThank you for choosing NORDBOOKING!";
        
        wp_mail($user->user_email, $subject, $message);
        $this->log("Welcome email sent to user {$user_id}");
    }
    
    private function sendPaymentConfirmationEmail($user_id, $invoice) {
        $user = get_userdata($user_id);
        if (!$user) return;
        
        $amount = $invoice->amount_paid / 100;
        $currency = strtoupper($invoice->currency);
        
        $subject = 'Payment Confirmation - NORDBOOKING Pro';
        $message = "Hi {$user->display_name},\n\nYour payment of {$currency} {$amount} has been processed successfully.\n\nInvoice: {$invoice->hosted_invoice_url}\n\nThank you!";
        
        wp_mail($user->user_email, $subject, $message);
        $this->log("Payment confirmation email sent to user {$user_id}");
    }
    
    private function sendPaymentFailedEmail($user_id, $invoice) {
        $user = get_userdata($user_id);
        if (!$user) return;
        
        $subject = 'Payment Failed - NORDBOOKING Pro';
        $message = "Hi {$user->display_name},\n\nWe were unable to process your payment. Please update your payment method to continue your subscription.\n\nManage your subscription: " . home_url('/dashboard/subscription/') . "\n\nIf you have questions, please contact support.";
        
        wp_mail($user->user_email, $subject, $message);
        $this->log("Payment failed email sent to user {$user_id}");
    }
    
    private function sendCancellationEmail($user_id) {
        $user = get_userdata($user_id);
        if (!$user) return;
        
        $subject = 'Subscription Cancelled - NORDBOOKING Pro';
        $message = "Hi {$user->display_name},\n\nYour NORDBOOKING Pro subscription has been cancelled. You'll continue to have access until the end of your billing period.\n\nIf you change your mind, you can resubscribe at: " . home_url('/dashboard/subscription/') . "\n\nThank you for using NORDBOOKING!";
        
        wp_mail($user->user_email, $subject, $message);
        $this->log("Cancellation email sent to user {$user_id}");
    }
    
    private function sendTrialEndingEmail($user_id) {
        $user = get_userdata($user_id);
        if (!$user) return;
        
        $subject = 'Your NORDBOOKING Trial is Ending Soon';
        $message = "Hi {$user->display_name},\n\nYour NORDBOOKING Pro trial will end in 3 days. To continue using all features, please add a payment method.\n\nManage your subscription: " . home_url('/dashboard/subscription/') . "\n\nThank you for trying NORDBOOKING!";
        
        wp_mail($user->user_email, $subject, $message);
        $this->log("Trial ending email sent to user {$user_id}");
    }
}

// Handle the webhook
$handler = new EnhancedStripeWebhookHandler();
$handler->handle();