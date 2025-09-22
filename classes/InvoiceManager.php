<?php

namespace NORDBOOKING\Classes;

/**
 * Invoice Manager for handling Stripe invoices
 * Provides functionality to fetch, display, and manage customer invoices
 */
class InvoiceManager {
    
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Hook into WordPress actions
        add_action('wp_ajax_nordbooking_get_invoices', [$this, 'handle_get_invoices']);
        add_action('wp_ajax_nordbooking_get_invoice_pdf', [$this, 'handle_get_invoice_pdf']);
        add_action('wp_ajax_nordbooking_admin_get_customer_invoices', [$this, 'handle_admin_get_customer_invoices']);
    }
    
    /**
     * Get invoices for a specific customer
     */
    public function get_customer_invoices($user_id, $limit = 20) {
        if (!StripeConfig::is_configured()) {
            return ['success' => false, 'message' => 'Stripe not configured'];
        }
        
        try {
            \Stripe\Stripe::setApiKey(StripeConfig::get_secret_key());
            
            // Get customer's Stripe customer ID
            $subscription = Subscription::get_subscription($user_id);
            if (!$subscription || empty($subscription['stripe_customer_id'])) {
                return ['success' => false, 'message' => 'No Stripe customer found'];
            }
            
            // Fetch invoices from Stripe
            $invoices = \Stripe\Invoice::all([
                'customer' => $subscription['stripe_customer_id'],
                'limit' => $limit,
                'status' => 'paid', // Only get paid invoices
                'expand' => ['data.subscription']
            ]);
            
            $formatted_invoices = [];
            foreach ($invoices->data as $invoice) {
                $formatted_invoices[] = $this->format_invoice($invoice);
            }
            
            return [
                'success' => true,
                'invoices' => $formatted_invoices,
                'total' => count($formatted_invoices)
            ];
            
        } catch (\Exception $e) {
            error_log('Invoice fetch failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to fetch invoices: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Format invoice data for display
     */
    private function format_invoice($stripe_invoice) {
        return [
            'id' => $stripe_invoice->id,
            'number' => $stripe_invoice->number,
            'amount' => $stripe_invoice->amount_paid,
            'currency' => strtoupper($stripe_invoice->currency),
            'status' => $stripe_invoice->status,
            'created' => $stripe_invoice->created,
            'created_formatted' => date_i18n(get_option('date_format'), $stripe_invoice->created),
            'period_start' => $stripe_invoice->period_start,
            'period_end' => $stripe_invoice->period_end,
            'period_start_formatted' => date_i18n(get_option('date_format'), $stripe_invoice->period_start),
            'period_end_formatted' => date_i18n(get_option('date_format'), $stripe_invoice->period_end),
            'hosted_invoice_url' => $stripe_invoice->hosted_invoice_url,
            'invoice_pdf' => $stripe_invoice->invoice_pdf,
            'description' => $stripe_invoice->description ?: 'NORDBOOKING Pro Subscription',
            'subscription_id' => $stripe_invoice->subscription
        ];
    }
    
    /**
     * Get invoice PDF URL
     */
    public function get_invoice_pdf_url($invoice_id) {
        if (!StripeConfig::is_configured()) {
            return null;
        }
        
        try {
            \Stripe\Stripe::setApiKey(StripeConfig::get_secret_key());
            $invoice = \Stripe\Invoice::retrieve($invoice_id);
            return $invoice->invoice_pdf;
        } catch (\Exception $e) {
            error_log('Failed to get invoice PDF URL: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Handle AJAX request to get invoices for current user
     */
    public function handle_get_invoices() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'nordbooking_dashboard_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => 'User not logged in']);
            return;
        }
        
        $result = $this->get_customer_invoices($user_id);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Handle AJAX request to get invoice PDF
     */
    public function handle_get_invoice_pdf() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'nordbooking_dashboard_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => 'User not logged in']);
            return;
        }
        
        $invoice_id = sanitize_text_field($_POST['invoice_id'] ?? '');
        if (empty($invoice_id)) {
            wp_send_json_error(['message' => 'Invoice ID required']);
            return;
        }
        
        // Verify user owns this invoice
        if (!$this->user_owns_invoice($user_id, $invoice_id)) {
            wp_send_json_error(['message' => 'Access denied']);
            return;
        }
        
        $pdf_url = $this->get_invoice_pdf_url($invoice_id);
        if ($pdf_url) {
            wp_send_json_success(['pdf_url' => $pdf_url]);
        } else {
            wp_send_json_error(['message' => 'Failed to get PDF URL']);
        }
    }
    
    /**
     * Handle admin AJAX request to get customer invoices
     */
    public function handle_admin_get_customer_invoices() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'nordbooking_admin_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }
        
        $user_id = intval($_POST['user_id'] ?? 0);
        if (!$user_id) {
            wp_send_json_error(['message' => 'User ID required']);
            return;
        }
        
        $result = $this->get_customer_invoices($user_id);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Verify that a user owns a specific invoice
     */
    private function user_owns_invoice($user_id, $invoice_id) {
        try {
            \Stripe\Stripe::setApiKey(StripeConfig::get_secret_key());
            
            // Get user's Stripe customer ID
            $subscription = Subscription::get_subscription($user_id);
            if (!$subscription || empty($subscription['stripe_customer_id'])) {
                return false;
            }
            
            // Get the invoice and check if it belongs to this customer
            $invoice = \Stripe\Invoice::retrieve($invoice_id);
            return $invoice->customer === $subscription['stripe_customer_id'];
            
        } catch (\Exception $e) {
            error_log('Invoice ownership verification failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get invoice statistics for admin dashboard
     */
    public function get_invoice_statistics() {
        if (!StripeConfig::is_configured()) {
            return [
                'total_invoices' => 0,
                'total_revenue' => 0,
                'this_month_revenue' => 0,
                'currency' => 'USD'
            ];
        }
        
        try {
            \Stripe\Stripe::setApiKey(StripeConfig::get_secret_key());
            
            // Get all paid invoices
            $invoices = \Stripe\Invoice::all([
                'limit' => 100,
                'status' => 'paid'
            ]);
            
            $total_revenue = 0;
            $this_month_revenue = 0;
            $currency = 'USD';
            $current_month = date('Y-m');
            
            foreach ($invoices->data as $invoice) {
                $total_revenue += $invoice->amount_paid;
                $currency = strtoupper($invoice->currency);
                
                if (date('Y-m', $invoice->created) === $current_month) {
                    $this_month_revenue += $invoice->amount_paid;
                }
            }
            
            return [
                'total_invoices' => count($invoices->data),
                'total_revenue' => $total_revenue / 100, // Convert from cents
                'this_month_revenue' => $this_month_revenue / 100,
                'currency' => $currency
            ];
            
        } catch (\Exception $e) {
            error_log('Invoice statistics failed: ' . $e->getMessage());
            return [
                'total_invoices' => 0,
                'total_revenue' => 0,
                'this_month_revenue' => 0,
                'currency' => 'USD'
            ];
        }
    }
}