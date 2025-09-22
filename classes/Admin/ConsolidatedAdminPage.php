<?php
/**
 * NORDBOOKING Consolidated Admin Page
 * Combines all admin functionality into one comprehensive dashboard
 */

namespace NORDBOOKING\Classes\Admin;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ConsolidatedAdminPage {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'register_admin_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_nordbooking_delete_user', array($this, 'handle_delete_user'));
        add_action('wp_ajax_nordbooking_health_check', array($this, 'handle_health_check'));
        add_action('wp_ajax_nordbooking_performance_stats', array($this, 'handle_performance_stats'));
        add_action('wp_ajax_nordbooking_slow_queries', array($this, 'handle_slow_queries'));
        add_action('wp_ajax_nordbooking_subscription_action', array($this, 'handle_subscription_action'));
        add_action('wp_ajax_nordbooking_get_subscriptions', array($this, 'handle_get_subscriptions'));
        add_action('wp_ajax_nordbooking_run_subscription_test', array($this, 'handle_run_subscription_test'));
        add_action('wp_ajax_nordbooking_sync_all_subscriptions', array($this, 'handle_sync_all_subscriptions'));
        add_action('wp_ajax_nordbooking_admin_get_customer_invoices', array($this, 'handle_admin_get_customer_invoices'));
    }

    public function register_admin_page() {
        // Remove existing menu pages to avoid conflicts
        remove_menu_page('NORDBOOKING-admin');
        remove_submenu_page('tools.php', 'nordbooking-performance');
        remove_submenu_page('tools.php', 'nordbooking-debug-performance');
        
        // Add main consolidated admin page
        add_menu_page(
            __('Nord Booking', 'NORDBOOKING'),
            __('Nord Booking', 'NORDBOOKING'),
            'manage_options',
            'nordbooking-consolidated-admin',
            array($this, 'render_admin_page'),
            'dashicons-calendar-alt',
            25
        );
    }

    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'toplevel_page_nordbooking-consolidated-admin') {
            return;
        }
        
        wp_enqueue_script('jquery');
        
        // Create a custom script handle for our admin scripts
        wp_register_script(
            'nordbooking-admin-scripts',
            '',
            array('jquery'),
            '1.0.0',
            true
        );
        wp_enqueue_script('nordbooking-admin-scripts');
        
        wp_localize_script('nordbooking-admin-scripts', 'nordbooking_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('nordbooking_admin_nonce'),
            'ajaxurl' => admin_url('admin-ajax.php') // Include ajaxurl in the same object
        ));
    }

    public function render_admin_page() {
        $auth_class = '\NORDBOOKING\Classes\Auth';
        
        // Handle form submissions
        $this->handle_form_submissions();
        
        ?>
        <div class="wrap nordbooking-admin-wrap">
            <h1><?php _e('Nord Booking Administration', 'NORDBOOKING'); ?></h1>
            
            <!-- Tab Navigation -->
            <h2 class="nav-tab-wrapper">
                <a href="#dashboard" class="nav-tab nav-tab-active"><?php _e('Dashboard', 'NORDBOOKING'); ?></a>
                <a href="#user-management" class="nav-tab"><?php _e('User Management', 'NORDBOOKING'); ?></a>
                <a href="#subscription-management" class="nav-tab"><?php _e('Subscription Management', 'NORDBOOKING'); ?></a>
                <a href="#performance" class="nav-tab"><?php _e('Performance', 'NORDBOOKING'); ?></a>
                <a href="#debug" class="nav-tab"><?php _e('Debug', 'NORDBOOKING'); ?></a>
                <a href="#stripe-settings" class="nav-tab"><?php _e('Stripe Settings', 'NORDBOOKING'); ?></a>
            </h2>

            <!-- Dashboard Tab -->
            <div id="dashboard" class="tab-content">
                <?php $this->render_dashboard_tab(); ?>
            </div>

            <!-- User Management Tab -->
            <div id="user-management" class="tab-content" style="display: none;">
                <?php $this->render_user_management_tab(); ?>
            </div>

            <!-- Subscription Management Tab -->
            <div id="subscription-management" class="tab-content" style="display: none;">
                <?php $this->render_subscription_management_tab(); ?>
            </div>

            <!-- Performance Tab -->
            <div id="performance" class="tab-content" style="display: none;">
                <?php $this->render_performance_tab(); ?>
            </div>

            <!-- Debug Tab -->
            <div id="debug" class="tab-content" style="display: none;">
                <?php $this->render_debug_tab(); ?>
            </div>

            <!-- Stripe Settings Tab -->
            <div id="stripe-settings" class="tab-content" style="display: none;">
                <?php $this->render_stripe_settings_tab(); ?>
            </div>
        </div>

        <style>
        .nordbooking-admin-wrap .tab-content {
            margin-top: 20px;
        }
        .kpi-card-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .kpi-card {
            background: #fff;
            padding: 20px;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .kpi-card .icon {
            font-size: 32px;
            color: #0073aa;
        }
        .kpi-card .value {
            font-size: 24px;
            font-weight: bold;
            color: #23282d;
        }
        .kpi-card .label {
            color: #666;
            font-size: 14px;
        }
        .performance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .performance-metric {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .performance-metric:last-child {
            border-bottom: none;
        }
        .health-status-healthy { color: #46b450; }
        .health-status-warning { color: #ffb900; }
        .health-status-critical { color: #dc3232; }
        .user-tree ul {
            list-style: none;
            margin-left: 20px;
        }
        .user-tree .owner-item {
            margin: 10px 0;
            padding: 10px;
            background: #f9f9f9;
            border-left: 4px solid #0073aa;
        }
        .user-tree .worker-list li {
            margin: 5px 0;
            padding: 8px;
            background: #fff;
            border-left: 2px solid #ddd;
        }
        .user-actions {
            margin-left: 10px;
        }
        .user-actions a, .user-actions button {
            margin-right: 5px;
        }
        .delete-user-btn {
            background: #dc3232;
            color: white;
            border: none;
            padding: 4px 8px;
            border-radius: 3px;
            cursor: pointer;
        }
        .delete-user-btn:hover {
            background: #a00;
        }
        .subscription-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .subscription-stat-card {
            background: #fff;
            padding: 15px;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            text-align: center;
        }
        .subscription-stat-card .number {
            font-size: 24px;
            font-weight: bold;
            color: #0073aa;
        }
        .subscription-stat-card .label {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }
        .subscription-filters {
            background: #f9f9f9;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 20px 0;
        }
        .subscription-filters select, .subscription-filters input {
            margin: 0 10px 10px 0;
        }
        .subscription-status {
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-active { background: #46b450; color: white; }
        .status-trial { background: #00a0d2; color: white; }
        .status-expired { background: #dc3232; color: white; }
        .status-cancelled { background: #ffb900; color: white; }
        .status-past_due { background: #ff6900; color: white; }
        .subscription-actions {
            white-space: nowrap;
        }
        .subscription-actions button {
            margin-right: 5px;
            padding: 4px 8px;
            font-size: 12px;
        }
        .subscription-table {
            margin-top: 20px;
        }
        .subscription-details {
            background: #f9f9f9;
            padding: 10px;
            margin: 5px 0;
            border-left: 4px solid #0073aa;
            font-size: 12px;
        }
        
        .subscription-health-card {
            background: #fff;
            padding: 20px;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .health-indicator {
            padding: 15px;
            border-radius: 4px;
            margin-top: 10px;
        }
        
        .health-healthy {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .health-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }
        
        .health-critical {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .health-issues, .health-warnings {
            margin-top: 10px;
        }
        
        .health-issues ul, .health-warnings ul {
            margin: 5px 0 0 20px;
        }
        
        .subscription-quick-actions {
            margin: 20px 0;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .test-modal {
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .test-modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 0;
            border: 1px solid #888;
            width: 90%;
            max-width: 800px;
            border-radius: 4px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .test-modal-header {
            padding: 20px;
            background: #f1f1f1;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .test-modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .test-modal-body {
            padding: 20px;
        }
        
        .invoice-modal {
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .invoice-modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 0;
            border: 1px solid #888;
            width: 90%;
            max-width: 900px;
            border-radius: 4px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .invoice-modal-header {
            padding: 20px;
            background: #f1f1f1;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .invoice-modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .invoice-modal-body {
            padding: 20px;
        }
        
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .invoice-table th,
        .invoice-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .invoice-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        
        .invoice-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .invoice-number {
            font-weight: 600;
            color: #0073aa;
        }
        
        .invoice-amount {
            font-weight: 600;
            color: #28a745;
        }
        
        .invoice-date {
            color: #666;
            font-size: 14px;
        }
        
        .invoice-period {
            color: #666;
            font-size: 12px;
            font-style: italic;
        }
        
        .invoice-actions {
            white-space: nowrap;
        }
        
        .invoice-download-btn {
            background: #0073aa;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 3px;
            cursor: pointer;
            text-decoration: none;
            font-size: 12px;
            margin-right: 5px;
        }
        
        .invoice-download-btn:hover {
            background: #005a87;
            color: white;
        }
        
        .invoice-view-btn {
            background: #6c757d;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 3px;
            cursor: pointer;
            text-decoration: none;
            font-size: 12px;
        }
        
        .invoice-view-btn:hover {
            background: #545b62;
            color: white;
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Ensure $ is available in this scope
            // Tab switching
            $('.nav-tab').click(function(e) {
                e.preventDefault();
                var target = $(this).attr('href');
                
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                $('.tab-content').hide();
                $(target).show();
                
                // Load performance data when performance tab is opened
                if (target === '#performance') {
                    loadPerformanceData();
                }
                
                // Load subscriptions when subscription tab is opened
                if (target === '#subscription-management') {
                    loadSubscriptions();
                }
            });

            // User deletion
            $('.delete-user-btn').click(function(e) {
                e.preventDefault();
                var userId = $(this).data('user-id');
                var userName = $(this).data('user-name');
                
                if (confirm('Are you sure you want to delete user "' + userName + '"? This action cannot be undone.')) {
                    $.post(nordbooking_admin.ajaxurl, {
                        action: 'nordbooking_delete_user',
                        user_id: userId,
                        _ajax_nonce: nordbooking_admin.nonce
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.data.message);
                        }
                    });
                }
            });

            // Toggle worker lists
            $('.toggle-workers').click(function() {
                var workerList = $(this).parent().find('.worker-list');
                workerList.toggle();
                $(this).text(workerList.is(':visible') ? '▼' : '▶');
            });

            // Subscription management
            $(document).on('click', '.subscription-action-btn', function(e) {
                e.preventDefault();
                var action = $(this).data('action');
                var userId = $(this).data('user-id');
                var userName = $(this).data('user-name');
                
                var confirmMessage = '';
                switch(action) {
                    case 'cancel':
                        confirmMessage = 'Are you sure you want to cancel the subscription for "' + userName + '"?';
                        break;
                    case 'reactivate':
                        confirmMessage = 'Are you sure you want to reactivate the subscription for "' + userName + '"?';
                        break;
                    case 'extend_trial':
                        confirmMessage = 'Are you sure you want to extend the trial for "' + userName + '"?';
                        break;
                    case 'force_expire':
                        confirmMessage = 'Are you sure you want to force expire the subscription for "' + userName + '"? This action cannot be undone.';
                        break;
                }
                
                if (confirm(confirmMessage)) {
                    $.post(nordbooking_admin.ajaxurl, {
                        action: 'nordbooking_subscription_action',
                        subscription_action: action,
                        user_id: userId,
                        _ajax_nonce: nordbooking_admin.nonce
                    }, function(response) {
                        if (response.success) {
                            loadSubscriptions();
                        } else {
                            alert('Error: ' + response.data.message);
                        }
                    });
                }
            });

            // Subscription filters
            $(document).on('change', '#subscription-status-filter, #subscription-search', function() {
                loadSubscriptions();
            });
            
            // Add refresh button functionality
            $(document).on('click', '#refresh-subscriptions', function(e) {
                e.preventDefault();
                loadSubscriptions();
            });
            
            // System test functionality
            $(document).on('click', '#run-system-test', function(e) {
                e.preventDefault();
                const button = $(this);
                const originalText = button.text();
                button.prop('disabled', true).text('Running Tests...');
                
                $.post(nordbooking_admin.ajaxurl, {
                    action: 'nordbooking_run_subscription_test',
                    _ajax_nonce: nordbooking_admin.nonce
                }, function(response) {
                    if (response.success) {
                        showTestResults(response.data.html_report);
                    } else {
                        alert('Test failed: ' + (response.data ? response.data.message : 'Unknown error'));
                    }
                }).fail(function() {
                    alert('Test failed: Network error');
                }).always(function() {
                    button.prop('disabled', false).text(originalText);
                });
            });
            
            // Sync all subscriptions
            $(document).on('click', '#sync-all-subscriptions', function(e) {
                e.preventDefault();
                if (confirm('This will sync all subscriptions with Stripe. This may take a few minutes. Continue?')) {
                    const button = $(this);
                    const originalText = button.text();
                    button.prop('disabled', true).text('Syncing...');
                    
                    $.post(nordbooking_admin.ajaxurl, {
                        action: 'nordbooking_sync_all_subscriptions',
                        _ajax_nonce: nordbooking_admin.nonce
                    }, function(response) {
                        if (response.success) {
                            alert('Sync completed successfully!');
                            location.reload(); // Refresh to show updated stats
                        } else {
                            alert('Sync failed: ' + (response.data ? response.data.message : 'Unknown error'));
                        }
                    }).fail(function() {
                        alert('Sync failed: Network error');
                    }).always(function() {
                        button.prop('disabled', false).text(originalText);
                    });
                }
            });
            
            // View customer invoices
            $(document).on('click', '.view-invoices-btn', function(e) {
                e.preventDefault();
                var userId = $(this).data('user-id');
                var userName = $(this).data('user-name');
                
                showCustomerInvoices(userId, userName);
            });
            
            // Show test results in modal
            function showTestResults(htmlReport) {
            // Create modal if it doesn't exist
            if ($('#test-results-modal').length === 0) {
                $('body').append(`
                    <div id="test-results-modal" class="test-modal">
                        <div class="test-modal-content">
                            <div class="test-modal-header">
                                <h3>System Test Results</h3>
                                <button class="test-modal-close">&times;</button>
                            </div>
                            <div class="test-modal-body">
                                <div id="test-results-content"></div>
                            </div>
                        </div>
                    </div>
                `);
                
                // Close modal functionality
                $(document).on('click', '.test-modal-close, .test-modal', function(e) {
                    if (e.target === this) {
                        $('#test-results-modal').hide();
                    }
                });
            }
            
            $('#test-results-content').html(htmlReport);
            $('#test-results-modal').show();
            }
            
            // Show customer invoices in modal
            function showCustomerInvoices(userId, userName) {
            // Create modal if it doesn't exist
            if ($('#invoice-modal').length === 0) {
                $('body').append(`
                    <div id="invoice-modal" class="invoice-modal">
                        <div class="invoice-modal-content">
                            <div class="invoice-modal-header">
                                <h3>Customer Invoices</h3>
                                <button class="invoice-modal-close">&times;</button>
                            </div>
                            <div class="invoice-modal-body">
                                <div id="invoice-modal-content"></div>
                            </div>
                        </div>
                    </div>
                `);
                
                // Close modal functionality
                $(document).on('click', '.invoice-modal-close, .invoice-modal', function(e) {
                    if (e.target === this) {
                        $('#invoice-modal').hide();
                    }
                });
            }
            
            // Update modal title
            $('.invoice-modal-header h3').text('Invoices for ' + userName);
            
            // Show loading
            $('#invoice-modal-content').html('<p>Loading invoices...</p>');
            $('#invoice-modal').show();
            
            // Load invoices
            $.post(nordbooking_admin.ajaxurl, {
                action: 'nordbooking_admin_get_customer_invoices',
                user_id: userId,
                _ajax_nonce: nordbooking_admin.nonce
            }, function(response) {
                if (response.success && response.data.invoices) {
                    displayCustomerInvoices(response.data.invoices);
                } else {
                    $('#invoice-modal-content').html('<p>No invoices found for this customer.</p>');
                }
            }).fail(function() {
                $('#invoice-modal-content').html('<p>Failed to load invoices. Please try again.</p>');
            });
            }
            
            // Display customer invoices in modal
            function displayCustomerInvoices(invoices) {
            if (invoices.length === 0) {
                $('#invoice-modal-content').html('<p>No invoices found for this customer.</p>');
                return;
            }
            
            let html = '<table class="invoice-table">';
            html += '<thead><tr>';
            html += '<th>Invoice</th>';
            html += '<th>Amount</th>';
            html += '<th>Date</th>';
            html += '<th>Period</th>';
            html += '<th>Actions</th>';
            html += '</tr></thead><tbody>';
            
            invoices.forEach(function(invoice) {
                html += '<tr>';
                html += '<td><span class="invoice-number">' + (invoice.number || invoice.id) + '</span></td>';
                html += '<td><span class="invoice-amount">' + invoice.currency + ' ' + (invoice.amount / 100).toFixed(2) + '</span></td>';
                html += '<td><span class="invoice-date">' + invoice.created_formatted + '</span></td>';
                html += '<td><span class="invoice-period">' + invoice.period_start_formatted + ' - ' + invoice.period_end_formatted + '</span></td>';
                html += '<td class="invoice-actions">';
                html += '<a href="' + invoice.invoice_pdf + '" target="_blank" class="invoice-download-btn" title="Download PDF">';
                html += '📄 PDF</a>';
                html += '<a href="' + invoice.hosted_invoice_url + '" target="_blank" class="invoice-view-btn" title="View Online">';
                html += '👁 View</a>';
                html += '</td>';
                html += '</tr>';
            });
            
            html += '</tbody></table>';
            
            $('#invoice-modal-content').html(html);
            }
        }); // End of jQuery(document).ready

        function loadPerformanceData() {
            // Load health status
            jQuery.post(nordbooking_admin.ajaxurl, {
                action: 'nordbooking_health_check',
                _ajax_nonce: nordbooking_admin.nonce
            }, function(response) {
                if (response.success) {
                    updateHealthStatus(response.data);
                }
            });

            // Load performance stats
            jQuery.post(nordbooking_admin.ajaxurl, {
                action: 'nordbooking_performance_stats',
                _ajax_nonce: nordbooking_admin.nonce
            }, function(response) {
                if (response.success) {
                    updatePerformanceStats(response.data);
                }
            });

            // Load slow queries
            jQuery.post(nordbooking_admin.ajaxurl, {
                action: 'nordbooking_slow_queries',
                _ajax_nonce: nordbooking_admin.nonce
            }, function(response) {
                if (response.success) {
                    updateSlowQueries(response.data);
                }
            });
        }

        function updateHealthStatus(health) {
            var html = '<div class="health-status-' + health.status + '">Status: ' + health.status.toUpperCase() + '</div>';
            html += '<div style="margin-top: 10px;">';
            Object.keys(health.checks).forEach(function(check) {
                var status = health.checks[check].status;
                html += '<div class="performance-metric">';
                html += '<span>' + check + ':</span>';
                html += '<span class="health-status-' + status + '">' + status + '</span>';
                html += '</div>';
            });
            html += '</div>';
            jQuery('#health-status').html(html);
        }

        function updatePerformanceStats(stats) {
            var html = '';
            
            if (stats.memory_usage) {
                html += '<div class="performance-metric">';
                html += '<span>Memory Usage:</span>';
                html += '<span>' + formatBytes(stats.memory_usage.current) + ' / ' + stats.memory_usage.limit + '</span>';
                html += '</div>';
            }
            
            if (stats.cache_manager) {
                html += '<div class="performance-metric">';
                html += '<span>Cache Hit Rate:</span>';
                html += '<span>' + stats.cache_manager.hit_rate + '%</span>';
                html += '</div>';
            }
            
            jQuery('#performance-stats').html(html || 'No performance data available');
        }

        function updateSlowQueries(queries) {
            if (queries.length > 0) {
                var html = '<table class="wp-list-table widefat fixed striped">';
                html += '<thead><tr><th>Query</th><th>Duration</th><th>Memory</th><th>Time</th></tr></thead>';
                html += '<tbody>';
                queries.forEach(function(query) {
                    html += '<tr>';
                    html += '<td>' + query.query_name + '</td>';
                    html += '<td>' + query.duration + 's</td>';
                    html += '<td>' + formatBytes(query.memory_used) + '</td>';
                    html += '<td>' + query.created_at + '</td>';
                    html += '</tr>';
                });
                html += '</tbody></table>';
                jQuery('#slow-queries').html(html);
            } else {
                jQuery('#slow-queries').html('<p>✅ No slow queries detected recently.</p>');
            }
        }

        function loadSubscriptions() {
            jQuery('#subscription-list').html('<tr><td colspan="8">Loading...</td></tr>');
            
            var statusFilter = jQuery('#subscription-status-filter').val();
            var searchTerm = jQuery('#subscription-search').val();
            
            jQuery.post(nordbooking_admin.ajaxurl, {
                action: 'nordbooking_get_subscriptions',
                status_filter: statusFilter,
                search_term: searchTerm,
                _ajax_nonce: nordbooking_admin.nonce
            }, function(response) {
                if (response.success) {
                    updateSubscriptionTable(response.data.subscriptions);
                    updateSubscriptionStats(response.data.stats);
                } else {
                    jQuery('#subscription-list').html('<tr><td colspan="8">Error loading subscriptions: ' + (response.data ? response.data.message : 'Unknown error') + '</td></tr>');
                }
            }).fail(function(xhr, status, error) {
                jQuery('#subscription-list').html('<tr><td colspan="8">Failed to load subscriptions: ' + error + '</td></tr>');
            });
        }

        function updateSubscriptionTable(subscriptions) {
            var html = '';
            if (subscriptions.length > 0) {
                subscriptions.forEach(function(sub) {
                    html += '<tr>';
                    html += '<td><strong>' + sub.user_name + '</strong><br><small>' + sub.user_email + '</small></td>';
                    html += '<td><span class="subscription-status status-' + sub.status + '">' + sub.status_label + '</span></td>';
                    html += '<td>' + (sub.trial_ends_at || 'N/A') + '</td>';
                    html += '<td>' + (sub.ends_at || 'N/A') + '</td>';
                    html += '<td>' + sub.created_at + '</td>';
                    html += '<td>' + (sub.stripe_customer_id ? 'Yes' : 'No') + '</td>';
                    html += '<td>' + (sub.amount ? '$' + (sub.amount / 100).toFixed(2) : 'N/A') + '</td>';
                    html += '<td class="subscription-actions">';
                    
                    // Add action buttons based on status
                    if (sub.status === 'active') {
                        html += '<button class="button button-small subscription-action-btn" data-action="cancel" data-user-id="' + sub.user_id + '" data-user-name="' + sub.user_name + '">Cancel</button>';
                    } else if (sub.status === 'cancelled') {
                        html += '<button class="button button-small subscription-action-btn" data-action="reactivate" data-user-id="' + sub.user_id + '" data-user-name="' + sub.user_name + '">Reactivate</button>';
                    } else if (sub.status === 'trial') {
                        html += '<button class="button button-small subscription-action-btn" data-action="extend_trial" data-user-id="' + sub.user_id + '" data-user-name="' + sub.user_name + '">Extend Trial</button>';
                    }
                    
                    if (sub.status !== 'expired') {
                        html += '<button class="button button-small button-link-delete subscription-action-btn" data-action="force_expire" data-user-id="' + sub.user_id + '" data-user-name="' + sub.user_name + '">Force Expire</button>';
                    }
                    
                    html += '<button class="button button-small view-invoices-btn" data-user-id="' + sub.user_id + '" data-user-name="' + sub.user_name + '">View Invoices</button>';
                    
                    html += '</td>';
                    html += '</tr>';
                });
            } else {
                html = '<tr><td colspan="8">No subscriptions found</td></tr>';
            }
            jQuery('#subscription-list').html(html);
        }

        function updateSubscriptionStats(stats) {
            jQuery('#stat-total').text(stats.total || 0);
            jQuery('#stat-active').text(stats.active || 0);
            jQuery('#stat-trial').text(stats.trial || 0);
            jQuery('#stat-expired').text(stats.expired || 0);
            jQuery('#stat-cancelled').text(stats.cancelled || 0);
            jQuery('#stat-mrr').text('$' + (stats.mrr || 0).toFixed(2));
        }

        function formatBytes(bytes) {
            if (bytes === 0) return '0 Bytes';
            var k = 1024;
            var sizes = ['Bytes', 'KB', 'MB', 'GB'];
            var i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        </script>
        <?php
    }  
  private function render_dashboard_tab() {
        $business_owners = $this->get_business_owners();
        
        // Calculate KPIs
        $total_owners = count($business_owners);
        $active_subscriptions = 0;
        $trial_users = 0;
        $mrr = 0;
        $subscription_price = get_option('nordbooking_stripe_subscription_price', 49);

        foreach ($business_owners as $owner) {
            if (get_user_meta($owner->ID, '_nordbooking_subscription_status', true) === 'active') {
                $active_subscriptions++;
                $mrr += $subscription_price;
            } elseif (get_user_meta($owner->ID, '_nordbooking_trial_ends_at', true) && time() < get_user_meta($owner->ID, '_nordbooking_trial_ends_at', true)) {
                $trial_users++;
            }
        }
        
        // Get invoice statistics
        $invoice_stats = ['total_invoices' => 0, 'total_revenue' => 0, 'currency' => 'USD'];
        if (class_exists('NORDBOOKING\Classes\InvoiceManager')) {
            $invoice_manager = \NORDBOOKING\Classes\InvoiceManager::getInstance();
            $invoice_stats = $invoice_manager->get_invoice_statistics();
        }
        ?>
        <div class="kpi-card-container">
            <div class="kpi-card">
                <div class="icon dashicons dashicons-groups"></div>
                <div class="info">
                    <div class="value"><?php echo $total_owners; ?></div>
                    <div class="label"><?php _e('Total Business Owners', 'NORDBOOKING'); ?></div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="icon dashicons dashicons-money-alt"></div>
                <div class="info">
                    <div class="value"><?php echo $active_subscriptions; ?></div>
                    <div class="label"><?php _e('Active Subscriptions', 'NORDBOOKING'); ?></div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="icon dashicons dashicons-clock"></div>
                <div class="info">
                    <div class="value"><?php echo $trial_users; ?></div>
                    <div class="label"><?php _e('Users on Trial', 'NORDBOOKING'); ?></div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="icon dashicons dashicons-chart-area"></div>
                <div class="info">
                    <div class="value">$<?php echo number_format($mrr, 2); ?></div>
                    <div class="label"><?php _e('Monthly Recurring Revenue', 'NORDBOOKING'); ?></div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="icon dashicons dashicons-media-document"></div>
                <div class="info">
                    <div class="value"><?php echo $invoice_stats['total_invoices']; ?></div>
                    <div class="label"><?php _e('Total Invoices', 'NORDBOOKING'); ?></div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="icon dashicons dashicons-money"></div>
                <div class="info">
                    <div class="value"><?php echo $invoice_stats['currency']; ?> <?php echo number_format($invoice_stats['total_revenue'], 2); ?></div>
                    <div class="label"><?php _e('Total Revenue', 'NORDBOOKING'); ?></div>
                </div>
            </div>
        </div>

        <h2><?php _e('Recent Business Owners', 'NORDBOOKING'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Display Name', 'NORDBOOKING'); ?></th>
                    <th><?php _e('Email', 'NORDBOOKING'); ?></th>
                    <th><?php _e('Subscription', 'NORDBOOKING'); ?></th>
                    <th><?php _e('Trial Ends', 'NORDBOOKING'); ?></th>
                    <th><?php _e('Registered', 'NORDBOOKING'); ?></th>
                    <th><?php _e('Actions', 'NORDBOOKING'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($business_owners)) : ?>
                    <?php foreach (array_slice($business_owners, 0, 10) as $user) : ?>
                        <tr>
                            <td><strong><?php echo esc_html($user->display_name); ?></strong></td>
                            <td><?php echo esc_html($user->user_email); ?></td>
                            <td><?php echo esc_html(get_user_meta($user->ID, '_nordbooking_subscription_status', true) ? ucfirst(get_user_meta($user->ID, '_nordbooking_subscription_status', true)) : 'None'); ?></td>
                            <td><?php echo esc_html(get_user_meta($user->ID, '_nordbooking_trial_ends_at', true) ? date(get_option('date_format'), get_user_meta($user->ID, '_nordbooking_trial_ends_at', true)) : 'N/A'); ?></td>
                            <td><?php echo esc_html(date(get_option('date_format'), strtotime($user->user_registered))); ?></td>
                            <td>
                                <?php
                                $switch_url = wp_nonce_url(
                                    add_query_arg(
                                        [
                                            'action' => 'switch_to_user',
                                            'user_id' => $user->ID,
                                        ],
                                        admin_url()
                                    ),
                                    'switch_to_user_' . $user->ID
                                );
                                ?>
                                <a href="<?php echo esc_url($switch_url); ?>" class="button button-small"><?php _e('Login as User', 'NORDBOOKING'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="6"><?php _e('No business owners found.', 'NORDBOOKING'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }

    private function render_user_management_tab() {
        $auth_class = '\NORDBOOKING\Classes\Auth';
        $business_owners = $this->get_business_owners();
        
        // Define roles for display
        $all_nordbooking_roles_display = [
            $auth_class::ROLE_BUSINESS_OWNER => __('Business Owner', 'NORDBOOKING'),
            $auth_class::ROLE_WORKER_STAFF   => __('Worker Staff', 'NORDBOOKING'),
        ];
        ?>
        <div class="user-management-section">
            <h2><?php _e('User Hierarchy', 'NORDBOOKING'); ?></h2>
            
            <div class="user-tree">
                <ul>
                    <?php if (!empty($business_owners)) : ?>
                        <?php foreach ($business_owners as $owner) : ?>
                            <li class="owner-item">
                                <span class="toggle-workers">▶</span>
                                <span class="user-info">
                                    <strong><?php echo esc_html($owner->display_name ?: $owner->user_login); ?></strong>
                                    (<?php echo esc_html($owner->user_email); ?>) - 
                                    <?php echo esc_html($all_nordbooking_roles_display[$auth_class::ROLE_BUSINESS_OWNER]); ?>
                                </span>
                                <span class="user-actions">
                                    <a href="<?php echo esc_url(get_edit_user_link($owner->ID)); ?>" target="_blank" class="button button-small"><?php _e('Edit', 'NORDBOOKING'); ?></a>
                                    <button type="button" class="delete-user-btn" data-user-id="<?php echo esc_attr($owner->ID); ?>" data-user-name="<?php echo esc_attr($owner->display_name ?: $owner->user_email); ?>">
                                        <?php _e('Delete', 'NORDBOOKING'); ?>
                                    </button>
                                </span>
                                
                                <?php
                                $workers_args = [
                                    'meta_key' => $auth_class::META_KEY_OWNER_ID,
                                    'meta_value' => $owner->ID,
                                    'orderby' => 'ID',
                                    'order' => 'ASC'
                                ];
                                $workers = get_users($workers_args);
                                ?>
                                <ul class="worker-list" style="display: none;">
                                    <?php if (!empty($workers)) : ?>
                                        <?php foreach ($workers as $worker) : ?>
                                            <?php
                                            $worker_role_name = __('N/A', 'NORDBOOKING');
                                            foreach ($worker->roles as $role_slug) {
                                                if (isset($all_nordbooking_roles_display[$role_slug])) {
                                                    $worker_role_name = $all_nordbooking_roles_display[$role_slug];
                                                    break;
                                                }
                                            }
                                            ?>
                                            <li>
                                                <span class="user-info">
                                                    <strong><?php echo esc_html($worker->display_name ?: $worker->user_login); ?></strong>
                                                    (<?php echo esc_html($worker->user_email); ?>) - 
                                                    <?php echo esc_html($worker_role_name); ?>
                                                </span>
                                                <span class="user-actions">
                                                    <a href="<?php echo esc_url(get_edit_user_link($worker->ID)); ?>" target="_blank" class="button button-small"><?php _e('Edit', 'NORDBOOKING'); ?></a>
                                                    <button type="button" class="delete-user-btn" data-user-id="<?php echo esc_attr($worker->ID); ?>" data-user-name="<?php echo esc_attr($worker->display_name ?: $worker->user_email); ?>">
                                                        <?php _e('Delete', 'NORDBOOKING'); ?>
                                                    </button>
                                                </span>
                                            </li>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <li><?php _e('No workers found for this owner.', 'NORDBOOKING'); ?></li>
                                    <?php endif; ?>
                                </ul>
                            </li>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <li><?php _e('No Business Owners found.', 'NORDBOOKING'); ?></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Create New Worker Form -->
            <div class="postbox" style="margin-top: 30px;">
                <h2 class="hndle"><?php _e('Create New Worker Staff', 'NORDBOOKING'); ?></h2>
                <div class="inside">
                    <form method="post" action="">
                        <?php wp_nonce_field('nordbooking_create_worker_staff_nonce', '_nordbooking_create_staff_nonce', true, true); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Email Address', 'NORDBOOKING'); ?></th>
                                <td><input type="email" name="nordbooking_new_staff_email" required class="regular-text" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Password', 'NORDBOOKING'); ?></th>
                                <td><input type="password" name="nordbooking_new_staff_password" required class="regular-text" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('First Name', 'NORDBOOKING'); ?></th>
                                <td><input type="text" name="nordbooking_new_staff_first_name" class="regular-text" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Last Name', 'NORDBOOKING'); ?></th>
                                <td><input type="text" name="nordbooking_new_staff_last_name" class="regular-text" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Assign to Business Owner', 'NORDBOOKING'); ?></th>
                                <td>
                                    <select name="nordbooking_new_staff_owner_id" required>
                                        <option value=""><?php _e('-- Select Business Owner --', 'NORDBOOKING'); ?></option>
                                        <?php foreach ($business_owners as $owner) : ?>
                                            <option value="<?php echo esc_attr($owner->ID); ?>">
                                                <?php echo esc_html($owner->display_name ?: $owner->user_email); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <input type="submit" name="nordbooking_create_worker_staff_submit" class="button button-primary" value="<?php _e('Create Worker Staff', 'NORDBOOKING'); ?>" />
                        </p>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_subscription_management_tab() {
        $subscription_manager = \NORDBOOKING\Classes\SubscriptionManager::getInstance();
        $analytics = $subscription_manager->get_subscription_analytics();
        $health = $subscription_manager->get_health_status();
        
        ?>
        <div class="subscription-management-section">
            <h2><?php _e('Enhanced Subscription Management', 'NORDBOOKING'); ?></h2>
            
            <!-- System Health Status -->
            <div class="subscription-health-card">
                <h3>🏥 System Health</h3>
                <div class="health-indicator health-<?php echo esc_attr($health['overall_status']); ?>">
                    <span class="health-status"><?php echo ucfirst($health['overall_status']); ?></span>
                    <?php if (!empty($health['issues'])): ?>
                        <div class="health-issues">
                            <strong>Critical Issues:</strong>
                            <ul>
                                <?php foreach ($health['issues'] as $issue): ?>
                                    <li><?php echo esc_html($issue); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($health['warnings'])): ?>
                        <div class="health-warnings">
                            <strong>Warnings:</strong>
                            <ul>
                                <?php foreach ($health['warnings'] as $warning): ?>
                                    <li><?php echo esc_html($warning); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="subscription-quick-actions">
                <button id="run-system-test" class="button button-primary">🧪 Run System Test</button>
                <button id="sync-all-subscriptions" class="button button-secondary">🔄 Sync All Subscriptions</button>
                <button id="export-subscription-data" class="button button-secondary">📊 Export Data</button>
                <a href="<?php echo home_url('/wp-content/themes/' . get_template() . '/test-subscription-system.php'); ?>" 
                   target="_blank" class="button button-secondary">🔧 Advanced Testing</a>
            </div>
            
            <!-- Subscription Statistics -->
            <div class="subscription-stats-grid">
                <div class="subscription-stat-card">
                    <div class="number" id="stat-total">-</div>
                    <div class="label"><?php _e('Total Subscriptions', 'NORDBOOKING'); ?></div>
                </div>
                <div class="subscription-stat-card">
                    <div class="number" id="stat-active">-</div>
                    <div class="label"><?php _e('Active Subscriptions', 'NORDBOOKING'); ?></div>
                </div>
                <div class="subscription-stat-card">
                    <div class="number" id="stat-trial">-</div>
                    <div class="label"><?php _e('Trial Users', 'NORDBOOKING'); ?></div>
                </div>
                <div class="subscription-stat-card">
                    <div class="number" id="stat-expired">-</div>
                    <div class="label"><?php _e('Expired', 'NORDBOOKING'); ?></div>
                </div>
                <div class="subscription-stat-card">
                    <div class="number" id="stat-cancelled">-</div>
                    <div class="label"><?php _e('Cancelled', 'NORDBOOKING'); ?></div>
                </div>
                <div class="subscription-stat-card">
                    <div class="number" id="stat-mrr">-</div>
                    <div class="label"><?php _e('Monthly Recurring Revenue', 'NORDBOOKING'); ?></div>
                </div>
            </div>

            <!-- Filters -->
            <div class="subscription-filters">
                <label for="subscription-status-filter"><?php _e('Filter by Status:', 'NORDBOOKING'); ?></label>
                <select id="subscription-status-filter">
                    <option value=""><?php _e('All Statuses', 'NORDBOOKING'); ?></option>
                    <option value="active"><?php _e('Active', 'NORDBOOKING'); ?></option>
                    <option value="trial"><?php _e('Trial', 'NORDBOOKING'); ?></option>
                    <option value="cancelled"><?php _e('Cancelled', 'NORDBOOKING'); ?></option>
                    <option value="expired"><?php _e('Expired', 'NORDBOOKING'); ?></option>
                    <option value="past_due"><?php _e('Past Due', 'NORDBOOKING'); ?></option>
                </select>
                
                <label for="subscription-search"><?php _e('Search:', 'NORDBOOKING'); ?></label>
                <input type="text" id="subscription-search" placeholder="<?php _e('Search by name or email...', 'NORDBOOKING'); ?>" />
                
                <button type="button" id="refresh-subscriptions" class="button"><?php _e('Refresh', 'NORDBOOKING'); ?></button>
            </div>

            <!-- Debug Information -->
            <div class="postbox" style="margin-bottom: 20px;">
                <h2 class="hndle"><?php _e('Debug Information', 'NORDBOOKING'); ?></h2>
                <div class="inside">
                    <?php
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'nordbooking_subscriptions';
                    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
                    
                    echo '<p><strong>Subscription table exists:</strong> ' . ($table_exists ? '✅ Yes' : '❌ No') . '</p>';
                    
                    if ($table_exists) {
                        $total_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
                        echo '<p><strong>Total subscriptions in database:</strong> ' . $total_count . '</p>';
                        
                        $auth_class = '\NORDBOOKING\Classes\Auth';
                        $business_owners_count = count(get_users(['role' => $auth_class::ROLE_BUSINESS_OWNER]));
                        echo '<p><strong>Total business owners:</strong> ' . $business_owners_count . '</p>';
                        
                        if ($total_count < $business_owners_count) {
                            echo '<p style="color: orange;"><strong>⚠️ Some business owners may be missing subscriptions.</strong></p>';
                            echo '<p><a href="' . get_template_directory_uri() . '/fix-missing-subscriptions.php" target="_blank" class="button">Fix Missing Subscriptions</a></p>';
                        }
                    } else {
                        echo '<p style="color: red;"><strong>❌ Subscription table is missing. This will cause issues.</strong></p>';
                        echo '<p><a href="' . get_template_directory_uri() . '/fix-missing-subscriptions.php" target="_blank" class="button button-primary">Create Table & Fix Subscriptions</a></p>';
                    }
                    ?>
                </div>
            </div>

            <!-- Subscription Table -->
            <div class="subscription-table">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('User', 'NORDBOOKING'); ?></th>
                            <th><?php _e('Status', 'NORDBOOKING'); ?></th>
                            <th><?php _e('Trial Ends', 'NORDBOOKING'); ?></th>
                            <th><?php _e('Subscription Ends', 'NORDBOOKING'); ?></th>
                            <th><?php _e('Created', 'NORDBOOKING'); ?></th>
                            <th><?php _e('Stripe Customer', 'NORDBOOKING'); ?></th>
                            <th><?php _e('Amount', 'NORDBOOKING'); ?></th>
                            <th><?php _e('Actions', 'NORDBOOKING'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="subscription-list">
                        <tr>
                            <td colspan="8"><?php _e('Click refresh to load subscriptions...', 'NORDBOOKING'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Bulk Actions -->
            <div class="postbox" style="margin-top: 30px;">
                <h2 class="hndle"><?php _e('Bulk Actions', 'NORDBOOKING'); ?></h2>
                <div class="inside">
                    <p><?php _e('Select actions to perform on multiple subscriptions:', 'NORDBOOKING'); ?></p>
                    <form method="post" action="">
                        <?php wp_nonce_field('nordbooking_bulk_subscription_actions', '_nordbooking_bulk_nonce', true, true); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Action', 'NORDBOOKING'); ?></th>
                                <td>
                                    <select name="bulk_action">
                                        <option value=""><?php _e('-- Select Action --', 'NORDBOOKING'); ?></option>
                                        <option value="extend_trials"><?php _e('Extend All Trials by 7 Days', 'NORDBOOKING'); ?></option>
                                        <option value="send_renewal_reminders"><?php _e('Send Renewal Reminders', 'NORDBOOKING'); ?></option>
                                        <option value="cleanup_expired"><?php _e('Cleanup Expired Subscriptions', 'NORDBOOKING'); ?></option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <input type="submit" name="execute_bulk_action" class="button button-primary" value="<?php _e('Execute Bulk Action', 'NORDBOOKING'); ?>" onclick="return confirm('<?php _e('Are you sure you want to execute this bulk action?', 'NORDBOOKING'); ?>')" />
                        </p>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_performance_tab() {
        ?>
        <div class="performance-section">
            <h2><?php _e('Performance Dashboard', 'NORDBOOKING'); ?></h2>
            
            <?php
            // Show overall system status
            $all_good = class_exists('\NORDBOOKING\Performance\CacheManager') && 
                       class_exists('\NORDBOOKING\Performance\QueryProfiler');
            
            if ($all_good) {
                echo '<div class="notice notice-success inline">';
                echo '<p><strong>✅ System Status:</strong> Performance monitoring is active and working properly.</p>';
                echo '</div>';
            } else {
                echo '<div class="notice notice-warning inline">';
                echo '<p><strong>⚠️ System Status:</strong> Some performance features may not be fully loaded.</p>';
                echo '</div>';
            }
            ?>
            
            <div class="performance-grid">
                <!-- System Health -->
                <div class="postbox">
                    <h2 class="hndle"><?php _e('System Health', 'NORDBOOKING'); ?></h2>
                    <div class="inside">
                        <div id="health-status"><?php _e('Loading...', 'NORDBOOKING'); ?></div>
                        <button type="button" class="button" onclick="loadPerformanceData()"><?php _e('Refresh', 'NORDBOOKING'); ?></button>
                    </div>
                </div>

                <!-- Performance Stats -->
                <div class="postbox">
                    <h2 class="hndle"><?php _e('Performance Statistics', 'NORDBOOKING'); ?></h2>
                    <div class="inside">
                        <div id="performance-stats"><?php _e('Loading...', 'NORDBOOKING'); ?></div>
                    </div>
                </div>

                <!-- Cache Management -->
                <div class="postbox">
                    <h2 class="hndle"><?php _e('Cache Management', 'NORDBOOKING'); ?></h2>
                    <div class="inside">
                        <div id="cache-stats">
                            <?php
                            if (class_exists('\NORDBOOKING\Performance\CacheManager')) {
                                $cache_stats = \NORDBOOKING\Performance\CacheManager::getStats();
                                echo '<div class="performance-metric">';
                                echo '<span>' . __('Cache Hit Rate:', 'NORDBOOKING') . '</span>';
                                echo '<span>' . $cache_stats['hit_rate'] . '%</span>';
                                echo '</div>';
                                echo '<div class="performance-metric">';
                                echo '<span>' . __('Cache Hits:', 'NORDBOOKING') . '</span>';
                                echo '<span>' . $cache_stats['hits'] . '</span>';
                                echo '</div>';
                                echo '<div class="performance-metric">';
                                echo '<span>' . __('Cache Misses:', 'NORDBOOKING') . '</span>';
                                echo '<span>' . $cache_stats['misses'] . '</span>';
                                echo '</div>';
                            } else {
                                echo '<p style="color: orange;">' . __('Cache Manager not loaded.', 'NORDBOOKING') . '</p>';
                            }
                            ?>
                        </div>
                        <form method="post" style="margin-top: 10px;">
                            <?php wp_nonce_field('clear_cache', '_clear_cache_nonce', true, true); ?>
                            <input type="submit" name="clear_cache" class="button button-secondary" value="<?php _e('Clear All Cache', 'NORDBOOKING'); ?>" onclick="return confirm('<?php _e('Are you sure you want to clear all cache?', 'NORDBOOKING'); ?>')" />
                        </form>
                    </div>
                </div>

                <!-- Database Optimization -->
                <div class="postbox">
                    <h2 class="hndle"><?php _e('Database Optimization', 'NORDBOOKING'); ?></h2>
                    <div class="inside">
                        <p><?php _e('Last optimization:', 'NORDBOOKING'); ?> <?php echo get_option('nordbooking_last_optimization', __('Never', 'NORDBOOKING')); ?></p>
                        <form method="post">
                            <?php wp_nonce_field('optimize_db', '_optimize_db_nonce', true, true); ?>
                            <input type="submit" name="optimize_db" class="button button-primary" value="<?php _e('Optimize Database', 'NORDBOOKING'); ?>" onclick="return confirm('<?php _e('This will add missing indexes to improve performance. Continue?', 'NORDBOOKING'); ?>')" />
                        </form>
                    </div>
                </div>
            </div>

            <!-- Query Log -->
            <div class="postbox" style="margin-top: 20px;">
                <h2 class="hndle"><?php _e('Recent Slow Queries', 'NORDBOOKING'); ?></h2>
                <div class="inside">
                    <div id="slow-queries"><?php _e('Loading...', 'NORDBOOKING'); ?></div>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_debug_tab() {
        ?>
        <div class="debug-section">
            <h2><?php _e('Debug Information', 'NORDBOOKING'); ?></h2>
            
            <div class="postbox">
                <h2 class="hndle"><?php _e('File System Check', 'NORDBOOKING'); ?></h2>
                <div class="inside">
                    <?php
                    $performance_file = NORDBOOKING_THEME_DIR . 'performance_monitoring.php';
                    echo '<p><strong>' . __('Theme Directory:', 'NORDBOOKING') . '</strong> ' . NORDBOOKING_THEME_DIR . '</p>';
                    echo '<p><strong>' . __('Performance File Exists:', 'NORDBOOKING') . '</strong> ' . (file_exists($performance_file) ? '✅ ' . __('Yes', 'NORDBOOKING') : '❌ ' . __('No', 'NORDBOOKING')) . '</p>';
                    
                    if (file_exists($performance_file)) {
                        echo '<p><strong>' . __('File Size:', 'NORDBOOKING') . '</strong> ' . filesize($performance_file) . ' bytes</p>';
                        echo '<p><strong>' . __('File Readable:', 'NORDBOOKING') . '</strong> ' . (is_readable($performance_file) ? '✅ ' . __('Yes', 'NORDBOOKING') : '❌ ' . __('No', 'NORDBOOKING')) . '</p>';
                    }
                    ?>
                </div>
            </div>

            <div class="postbox">
                <h2 class="hndle"><?php _e('Class Loading Check', 'NORDBOOKING'); ?></h2>
                <div class="inside">
                    <?php
                    $classes = [
                        '\NORDBOOKING\Performance\QueryProfiler',
                        '\NORDBOOKING\Performance\CacheManager',
                        '\NORDBOOKING\Performance\DatabaseHealthMonitor',
                        '\NORDBOOKING\Performance\RateLimiter',
                        '\NORDBOOKING\Performance\PerformanceDashboard'
                    ];
                    
                    foreach ($classes as $class) {
                        $exists = class_exists($class);
                        echo '<p><strong>' . $class . ':</strong> ' . ($exists ? '✅ ' . __('Loaded', 'NORDBOOKING') : '❌ ' . __('Not Found', 'NORDBOOKING')) . '</p>';
                    }
                    ?>
                </div>
            </div>

            <div class="postbox">
                <h2 class="hndle"><?php _e('System Information', 'NORDBOOKING'); ?></h2>
                <div class="inside">
                    <ul>
                        <li><strong><?php _e('PHP Version:', 'NORDBOOKING'); ?></strong> <?php echo PHP_VERSION; ?></li>
                        <li><strong><?php _e('WordPress Version:', 'NORDBOOKING'); ?></strong> <?php echo get_bloginfo('version'); ?></li>
                        <li><strong><?php _e('Memory Limit:', 'NORDBOOKING'); ?></strong> <?php echo ini_get('memory_limit'); ?></li>
                        <li><strong><?php _e('Current Memory Usage:', 'NORDBOOKING'); ?></strong> <?php echo round(memory_get_usage(true) / 1024 / 1024, 2); ?> MB</li>
                        <li><strong><?php _e('Peak Memory Usage:', 'NORDBOOKING'); ?></strong> <?php echo round(memory_get_peak_usage(true) / 1024 / 1024, 2); ?> MB</li>
                        <li><strong><?php _e('WP Debug:', 'NORDBOOKING'); ?></strong> <?php echo (defined('WP_DEBUG') && WP_DEBUG ? '✅ ' . __('Enabled', 'NORDBOOKING') : '❌ ' . __('Disabled', 'NORDBOOKING')); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_stripe_settings_tab() {
        // Include Stripe settings functionality
        if (class_exists('\NORDBOOKING\Classes\StripeConfig')) {
            $stripe_config = new \NORDBOOKING\Classes\StripeConfig();
            
            // Handle form submission
            if (isset($_POST['submit_stripe_settings'])) {
                $stripe_config->save_settings($_POST);
                echo '<div class="notice notice-success"><p>' . __('Stripe settings saved successfully!', 'NORDBOOKING') . '</p></div>';
            }
            
            $settings = $stripe_config->get_settings();
            ?>
            <div class="stripe-settings-section">
                <h2><?php _e('Stripe Configuration', 'NORDBOOKING'); ?></h2>
                
                <form method="post" action="">
                    <?php wp_nonce_field('stripe_settings_nonce', '_stripe_settings_nonce', true, true); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Test Mode', 'NORDBOOKING'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="stripe_test_mode" value="1" <?php checked($settings['test_mode'] ?? false); ?> />
                                    <?php _e('Enable test mode', 'NORDBOOKING'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Live Publishable Key', 'NORDBOOKING'); ?></th>
                            <td><input type="text" name="stripe_live_publishable_key" value="<?php echo esc_attr($settings['live_publishable_key'] ?? ''); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Live Secret Key', 'NORDBOOKING'); ?></th>
                            <td><input type="password" name="stripe_live_secret_key" value="<?php echo esc_attr($settings['live_secret_key'] ?? ''); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Test Publishable Key', 'NORDBOOKING'); ?></th>
                            <td><input type="text" name="stripe_test_publishable_key" value="<?php echo esc_attr($settings['test_publishable_key'] ?? ''); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Test Secret Key', 'NORDBOOKING'); ?></th>
                            <td><input type="password" name="stripe_test_secret_key" value="<?php echo esc_attr($settings['test_secret_key'] ?? ''); ?>" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Webhook Endpoint Secret', 'NORDBOOKING'); ?></th>
                            <td><input type="password" name="stripe_webhook_secret" value="<?php echo esc_attr($settings['webhook_secret'] ?? ''); ?>" class="regular-text" /></td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="submit_stripe_settings" class="button button-primary" value="<?php _e('Save Stripe Settings', 'NORDBOOKING'); ?>" />
                    </p>
                </form>
            </div>
            <?php
        } else {
            echo '<p>' . __('Stripe configuration class not found.', 'NORDBOOKING') . '</p>';
        }
    }

    private function handle_form_submissions() {
        // Handle cache clearing
        if (isset($_POST['clear_cache']) && wp_verify_nonce($_POST['_wpnonce'], 'clear_cache')) {
            if (class_exists('\NORDBOOKING\Performance\CacheManager')) {
                \NORDBOOKING\Performance\CacheManager::flush();
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success is-dismissible"><p>' . __('Cache cleared successfully!', 'NORDBOOKING') . '</p></div>';
                });
            }
        }

        // Handle database optimization
        if (isset($_POST['optimize_db']) && wp_verify_nonce($_POST['_wpnonce'], 'optimize_db')) {
            if (class_exists('\NORDBOOKING\Classes\Database')) {
                \NORDBOOKING\Classes\Database::optimize_existing_tables();
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success is-dismissible"><p>' . __('Database optimization completed!', 'NORDBOOKING') . '</p></div>';
                });
            }
        }

        // Handle worker creation (reuse logic from UserManagementPage)
        if (isset($_POST['nordbooking_create_worker_staff_submit']) && check_admin_referer('nordbooking_create_worker_staff_nonce', '_nordbooking_create_staff_nonce')) {
            $this->handle_create_worker_staff();
        }

        // Handle bulk subscription actions
        if (isset($_POST['execute_bulk_action']) && check_admin_referer('nordbooking_bulk_subscription_actions', '_nordbooking_bulk_nonce')) {
            $this->handle_bulk_subscription_actions();
        }
    }

    private function handle_create_worker_staff() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Permission denied.', 'NORDBOOKING'));
        }

        $auth_class = '\NORDBOOKING\Classes\Auth';
        $new_staff_email = isset($_POST['nordbooking_new_staff_email']) ? sanitize_email($_POST['nordbooking_new_staff_email']) : '';
        $new_staff_password = isset($_POST['nordbooking_new_staff_password']) ? $_POST['nordbooking_new_staff_password'] : '';
        $new_staff_first_name = isset($_POST['nordbooking_new_staff_first_name']) ? sanitize_text_field($_POST['nordbooking_new_staff_first_name']) : '';
        $new_staff_last_name = isset($_POST['nordbooking_new_staff_last_name']) ? sanitize_text_field($_POST['nordbooking_new_staff_last_name']) : '';
        $selected_owner_id = isset($_POST['nordbooking_new_staff_owner_id']) ? intval($_POST['nordbooking_new_staff_owner_id']) : 0;

        $errors = new \WP_Error();

        // Validation
        if (empty($new_staff_email) || !is_email($new_staff_email)) {
            $errors->add('invalid_email', __('Valid email address is required.', 'NORDBOOKING'));
        }
        if (email_exists($new_staff_email)) {
            $errors->add('email_exists', __('This email address is already registered.', 'NORDBOOKING'));
        }
        if (empty($new_staff_password) || strlen($new_staff_password) < 7) {
            $errors->add('password_length', __('Password must be at least 7 characters long.', 'NORDBOOKING'));
        }
        if (empty($selected_owner_id)) {
            $errors->add('empty_owner', __('Assigning a Business Owner is required.', 'NORDBOOKING'));
        }

        if ($errors->has_errors()) {
            foreach ($errors->get_error_messages() as $message) {
                add_action('admin_notices', function() use ($message) {
                    echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($message) . '</p></div>';
                });
            }
        } else {
            // Create the user
            $user_data = array(
                'user_login' => $new_staff_email,
                'user_email' => $new_staff_email,
                'user_pass'  => $new_staff_password,
                'first_name' => $new_staff_first_name,
                'last_name'  => $new_staff_last_name,
                'role'       => $auth_class::ROLE_WORKER_STAFF,
            );
            $new_user_id = wp_insert_user($user_data);

            if (is_wp_error($new_user_id)) {
                add_action('admin_notices', function() use ($new_user_id) {
                    echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($new_user_id->get_error_message()) . '</p></div>';
                });
            } else {
                update_user_meta($new_user_id, $auth_class::META_KEY_OWNER_ID, $selected_owner_id);
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Worker Staff user created successfully.', 'NORDBOOKING') . '</p></div>';
                });
            }
        }
    }

    private function handle_bulk_subscription_actions() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Permission denied.', 'NORDBOOKING'));
        }

        $bulk_action = sanitize_text_field($_POST['bulk_action'] ?? '');
        
        if (empty($bulk_action)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Please select a bulk action.', 'NORDBOOKING') . '</p></div>';
            });
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'nordbooking_subscriptions';

        switch ($bulk_action) {
            case 'extend_trials':
                // Extend all active trials by 7 days
                $result = $wpdb->query(
                    "UPDATE $table_name 
                     SET trial_ends_at = DATE_ADD(trial_ends_at, INTERVAL 7 DAY) 
                     WHERE status = 'trial' AND trial_ends_at > NOW()"
                );
                
                add_action('admin_notices', function() use ($result) {
                    echo '<div class="notice notice-success is-dismissible"><p>' . 
                         sprintf(esc_html__('Extended %d trial subscriptions by 7 days.', 'NORDBOOKING'), $result) . 
                         '</p></div>';
                });
                break;

            case 'send_renewal_reminders':
                // Send renewal reminders to subscriptions ending soon
                $expiring_soon = $wpdb->get_results(
                    "SELECT user_id FROM $table_name 
                     WHERE status = 'active' 
                     AND ends_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 DAY)"
                );

                $notifications = new \NORDBOOKING\Classes\Notifications();
                $count = 0;
                
                foreach ($expiring_soon as $subscription) {
                    if ($notifications->send_renewal_reminder_email($subscription->user_id)) {
                        $count++;
                    }
                }

                add_action('admin_notices', function() use ($count) {
                    echo '<div class="notice notice-success is-dismissible"><p>' . 
                         sprintf(esc_html__('Sent renewal reminders to %d subscribers.', 'NORDBOOKING'), $count) . 
                         '</p></div>';
                });
                break;

            case 'cleanup_expired':
                // Clean up expired subscriptions older than 30 days
                $result = $wpdb->query(
                    "DELETE FROM $table_name 
                     WHERE status = 'expired' 
                     AND ends_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
                );

                add_action('admin_notices', function() use ($result) {
                    echo '<div class="notice notice-success is-dismissible"><p>' . 
                         sprintf(esc_html__('Cleaned up %d expired subscriptions.', 'NORDBOOKING'), $result) . 
                         '</p></div>';
                });
                break;

            default:
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Invalid bulk action selected.', 'NORDBOOKING') . '</p></div>';
                });
        }
    }

    public function handle_delete_user() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions'], 403);
        }

        if (!wp_verify_nonce($_POST['_ajax_nonce'], 'nordbooking_admin_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce'], 403);
        }

        $user_id = intval($_POST['user_id']);
        if ($user_id <= 0) {
            wp_send_json_error(['message' => 'Invalid user ID']);
        }

        // Don't allow deleting the current user
        if ($user_id === get_current_user_id()) {
            wp_send_json_error(['message' => 'Cannot delete your own account']);
        }

        // Delete the user
        if (wp_delete_user($user_id)) {
            wp_send_json_success(['message' => 'User deleted successfully']);
        } else {
            wp_send_json_error(['message' => 'Failed to delete user']);
        }
    }

    public function handle_health_check() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions'], 403);
        }

        if (!wp_verify_nonce($_POST['_ajax_nonce'], 'nordbooking_admin_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce'], 403);
        }

        if (class_exists('\NORDBOOKING\Performance\DatabaseHealthMonitor')) {
            $health = \NORDBOOKING\Performance\DatabaseHealthMonitor::checkHealth();
            wp_send_json_success($health);
        } else {
            // Fallback health check
            $health = [
                'status' => 'healthy',
                'checks' => [
                    'database' => ['status' => 'healthy'],
                    'memory' => ['status' => memory_get_usage(true) < (1024 * 1024 * 100) ? 'healthy' : 'warning'],
                    'cache' => ['status' => class_exists('\NORDBOOKING\Performance\CacheManager') ? 'healthy' : 'warning']
                ]
            ];
            wp_send_json_success($health);
        }
    }

    public function handle_performance_stats() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions'], 403);
        }

        if (!wp_verify_nonce($_POST['_ajax_nonce'], 'nordbooking_admin_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce'], 403);
        }

        $stats = [
            'memory_usage' => [
                'current' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
                'limit' => ini_get('memory_limit')
            ],
            'php_info' => [
                'version' => PHP_VERSION,
            ]
        ];

        // Try to get cache stats
        if (class_exists('\NORDBOOKING\Performance\CacheManager')) {
            try {
                $stats['cache_manager'] = \NORDBOOKING\Performance\CacheManager::getStats();
            } catch (Exception $e) {
                $stats['cache_manager_error'] = $e->getMessage();
            }
        }

        // Try to get query profiler stats
        if (class_exists('\NORDBOOKING\Performance\QueryProfiler')) {
            try {
                $stats['query_profiler'] = \NORDBOOKING\Performance\QueryProfiler::getStats();
            } catch (Exception $e) {
                $stats['query_profiler_error'] = $e->getMessage();
            }
        }

        wp_send_json_success($stats);
    }

    public function handle_slow_queries() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions'], 403);
        }

        if (!wp_verify_nonce($_POST['_ajax_nonce'], 'nordbooking_admin_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce'], 403);
        }

        global $wpdb;
        $query_log_table = $wpdb->prefix . 'nordbooking_query_log';

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$query_log_table'") != $query_log_table) {
            wp_send_json_success([]);
            return;
        }

        // Get recent slow queries
        $slow_queries = $wpdb->get_results(
            "SELECT query_name, duration, memory_used, created_at 
             FROM $query_log_table 
             WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
             ORDER BY duration DESC 
             LIMIT 10"
        );

        wp_send_json_success($slow_queries ?: []);
    }

    public function handle_subscription_action() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions'], 403);
        }

        if (!wp_verify_nonce($_POST['_ajax_nonce'], 'nordbooking_admin_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce'], 403);
        }

        $action = sanitize_text_field($_POST['subscription_action']);
        $user_id = intval($_POST['user_id']);

        if ($user_id <= 0) {
            wp_send_json_error(['message' => 'Invalid user ID']);
        }

        switch ($action) {
            case 'cancel':
                if (\NORDBOOKING\Classes\Subscription::cancel_subscription($user_id)) {
                    wp_send_json_success(['message' => 'Subscription cancelled successfully']);
                } else {
                    wp_send_json_error(['message' => 'Failed to cancel subscription']);
                }
                break;

            case 'reactivate':
                // Reactivate subscription logic
                global $wpdb;
                $table_name = $wpdb->prefix . 'nordbooking_subscriptions';
                $result = $wpdb->update(
                    $table_name,
                    ['status' => 'active'],
                    ['user_id' => $user_id]
                );
                
                if ($result !== false) {
                    wp_send_json_success(['message' => 'Subscription reactivated successfully']);
                } else {
                    wp_send_json_error(['message' => 'Failed to reactivate subscription']);
                }
                break;

            case 'extend_trial':
                // Extend trial by 7 days
                global $wpdb;
                $table_name = $wpdb->prefix . 'nordbooking_subscriptions';
                $subscription = \NORDBOOKING\Classes\Subscription::get_subscription($user_id);
                
                if ($subscription && $subscription['status'] === 'trial') {
                    $current_trial_end = new \DateTime($subscription['trial_ends_at']);
                    $new_trial_end = $current_trial_end->modify('+7 days');
                    
                    $result = $wpdb->update(
                        $table_name,
                        ['trial_ends_at' => $new_trial_end->format('Y-m-d H:i:s')],
                        ['user_id' => $user_id]
                    );
                    
                    if ($result !== false) {
                        wp_send_json_success(['message' => 'Trial extended by 7 days']);
                    } else {
                        wp_send_json_error(['message' => 'Failed to extend trial']);
                    }
                } else {
                    wp_send_json_error(['message' => 'User is not on trial']);
                }
                break;

            case 'force_expire':
                // Force expire subscription
                global $wpdb;
                $table_name = $wpdb->prefix . 'nordbooking_subscriptions';
                $result = $wpdb->update(
                    $table_name,
                    [
                        'status' => 'expired',
                        'ends_at' => current_time('mysql')
                    ],
                    ['user_id' => $user_id]
                );
                
                if ($result !== false) {
                    wp_send_json_success(['message' => 'Subscription expired successfully']);
                } else {
                    wp_send_json_error(['message' => 'Failed to expire subscription']);
                }
                break;

            default:
                wp_send_json_error(['message' => 'Invalid action']);
        }
    }

    public function handle_get_subscriptions() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions'], 403);
        }

        if (!wp_verify_nonce($_POST['_ajax_nonce'], 'nordbooking_admin_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce'], 403);
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'nordbooking_subscriptions';
        $users_table = $wpdb->prefix . 'users';

        $status_filter = sanitize_text_field($_POST['status_filter'] ?? '');
        $search_term = sanitize_text_field($_POST['search_term'] ?? '');

        // First, ensure the table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            // Create the table if it doesn't exist
            \NORDBOOKING\Classes\Subscription::install();
        }

        // Build query - get all subscriptions including those without Stripe data
        $where_conditions = ['1=1'];
        $query_params = [];

        if (!empty($status_filter)) {
            $where_conditions[] = 's.status = %s';
            $query_params[] = $status_filter;
        }

        if (!empty($search_term)) {
            $where_conditions[] = '(u.display_name LIKE %s OR u.user_email LIKE %s)';
            $query_params[] = '%' . $search_term . '%';
            $query_params[] = '%' . $search_term . '%';
        }

        $where_clause = implode(' AND ', $where_conditions);

        $query = "
            SELECT s.*, u.display_name, u.user_email, u.user_registered
            FROM $table_name s
            LEFT JOIN $users_table u ON s.user_id = u.ID
            WHERE $where_clause AND u.ID IS NOT NULL
            ORDER BY s.created_at DESC
            LIMIT 100
        ";

        try {
            if (!empty($query_params)) {
                $subscriptions = $wpdb->get_results($wpdb->prepare($query, $query_params), ARRAY_A);
            } else {
                $subscriptions = $wpdb->get_results($query, ARRAY_A);
            }

            if ($wpdb->last_error) {
                wp_send_json_error(['message' => 'Database error: ' . $wpdb->last_error]);
                return;
            }

            // Process subscriptions data
            $processed_subscriptions = [];
            foreach ($subscriptions as $sub) {
                $status = \NORDBOOKING\Classes\Subscription::get_subscription_status($sub['user_id']);
                
                $processed_subscriptions[] = [
                    'user_id' => $sub['user_id'],
                    'user_name' => $sub['display_name'] ?: 'Unknown',
                    'user_email' => $sub['user_email'],
                    'status' => $status,
                    'status_label' => ucfirst(str_replace('_', ' ', $status)),
                    'trial_ends_at' => $sub['trial_ends_at'] ? date('M j, Y', strtotime($sub['trial_ends_at'])) : null,
                    'ends_at' => $sub['ends_at'] ? date('M j, Y', strtotime($sub['ends_at'])) : null,
                    'created_at' => date('M j, Y', strtotime($sub['created_at'])),
                    'stripe_customer_id' => $sub['stripe_customer_id'],
                    'amount' => $this->get_subscription_amount($sub['stripe_subscription_id'])
                ];
            }

            // Calculate statistics
            $stats_query = "
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status = 'trial' THEN 1 ELSE 0 END) as trial,
                    SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
                FROM $table_name
            ";
            
            $stats = $wpdb->get_row($stats_query, ARRAY_A);
            
            // Calculate MRR (Monthly Recurring Revenue)
            $mrr = $this->calculate_mrr();
            $stats['mrr'] = $mrr;

            wp_send_json_success([
                'subscriptions' => $processed_subscriptions,
                'stats' => $stats
            ]);

        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Error loading subscriptions: ' . $e->getMessage()]);
        }
    }

    private function get_subscription_amount($stripe_subscription_id) {
        if (empty($stripe_subscription_id) || !class_exists('\NORDBOOKING\Classes\StripeConfig')) {
            return null;
        }

        if (!\NORDBOOKING\Classes\StripeConfig::is_configured()) {
            return null;
        }

        try {
            \Stripe\Stripe::setApiKey(\NORDBOOKING\Classes\StripeConfig::get_secret_key());
            $subscription = \Stripe\Subscription::retrieve($stripe_subscription_id);
            
            if ($subscription && !empty($subscription->items->data)) {
                return $subscription->items->data[0]->price->unit_amount;
            }
        } catch (\Exception $e) {
            // Silently fail for now
        }

        return null;
    }

    private function calculate_mrr() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'nordbooking_subscriptions';
        
        // Get active subscriptions count
        $active_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_name WHERE status = 'active'"
        );

        // Get subscription price from settings
        $subscription_price = get_option('nordbooking_stripe_subscription_price', 49);
        
        return $active_count * $subscription_price;
    }

    private function get_business_owners() {
        $auth_class = '\NORDBOOKING\Classes\Auth';
        return get_users([
            'role' => $auth_class::ROLE_BUSINESS_OWNER,
            'orderby' => 'registered',
            'order' => 'DESC'
        ]);
    }
    
    /**
     * Handle subscription test AJAX request
     */
    public function handle_run_subscription_test() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'nordbooking_admin_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }
        
        $test_user_id = intval($_POST['test_user_id'] ?? get_current_user_id());
        
        $tester = new \NORDBOOKING\Classes\SubscriptionTester();
        $results = $tester->run_complete_test($test_user_id);
        
        wp_send_json_success([
            'results' => $results,
            'html_report' => $tester->generate_html_report()
        ]);
    }
    
    /**
     * Handle sync all subscriptions AJAX request
     */
    public function handle_sync_all_subscriptions() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'nordbooking_admin_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }
        
        $subscription_manager = \NORDBOOKING\Classes\SubscriptionManager::getInstance();
        $subscription_manager->scheduled_sync_check();
        
        wp_send_json_success(['message' => 'All subscriptions synced successfully']);
    }
    
    /**
     * Handle admin request to get customer invoices
     */
    public function handle_admin_get_customer_invoices() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        if (!wp_verify_nonce($_POST['_ajax_nonce'] ?? '', 'nordbooking_admin_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
            return;
        }
        
        $user_id = intval($_POST['user_id'] ?? 0);
        if (!$user_id) {
            wp_send_json_error(['message' => 'User ID required']);
            return;
        }
        
        if (class_exists('NORDBOOKING\Classes\InvoiceManager')) {
            $invoice_manager = \NORDBOOKING\Classes\InvoiceManager::getInstance();
            $result = $invoice_manager->get_customer_invoices($user_id);
            
            if ($result['success']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error($result);
            }
        } else {
            wp_send_json_error(['message' => 'Invoice manager not available']);
        }
    }
}?>
