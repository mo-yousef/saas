<?php
/**
 * Comprehensive Subscription System Test Page
 * Access: yourdomain.com/wp-content/themes/yourtheme/test-subscription-system.php
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
    die('Could not load WordPress. Please run this script from your theme directory.');
}

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('You must be logged in as an administrator to run this test.');
}

// Handle AJAX test request
if (isset($_POST['run_test']) && wp_verify_nonce($_POST['nonce'], 'subscription_test_nonce')) {
    $test_user_id = intval($_POST['test_user_id'] ?? get_current_user_id());
    
    $tester = new \NORDBOOKING\Classes\SubscriptionTester();
    $results = $tester->run_complete_test($test_user_id);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'results' => $results,
        'html_report' => $tester->generate_html_report()
    ]);
    exit;
}

// Get all business owners for testing
$auth_class = '\NORDBOOKING\Classes\Auth';
$business_owners = get_users([
    'role' => $auth_class::ROLE_BUSINESS_OWNER,
    'orderby' => 'registered',
    'order' => 'DESC'
]);

?>
<!DOCTYPE html>
<html>
<head>
    <title>NORDBOOKING Subscription System Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f1f1f1;
        }
        
        .test-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .test-header {
            border-bottom: 2px solid #0073aa;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .test-controls {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group select, .form-group input {
            width: 100%;
            max-width: 300px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .button {
            background: #0073aa;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .button:hover {
            background: #005a87;
        }
        
        .button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .button-secondary {
            background: #666;
        }
        
        .button-secondary:hover {
            background: #444;
        }
        
        .test-results {
            margin-top: 30px;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .loading::after {
            content: '';
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #0073aa;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s linear infinite;
            margin-left: 10px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .test-summary {
            background: #f0f8ff;
            border: 1px solid #0073aa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .test-details table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .test-details th,
        .test-details td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .test-details th {
            background: #f5f5f5;
            font-weight: bold;
        }
        
        .test-details tr:hover {
            background: #f9f9f9;
        }
        
        .status-pass {
            color: #46b450;
            font-weight: bold;
        }
        
        .status-fail {
            color: #dc3232;
            font-weight: bold;
        }
        
        .quick-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .info-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .success-box {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .error-box {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <div class="test-header">
            <h1>🧪 NORDBOOKING Subscription System Test</h1>
            <p>Comprehensive testing suite for subscription functionality, real-time sync, and system reliability.</p>
        </div>
        
        <div class="info-box">
            <strong>What this test covers:</strong>
            <ul>
                <li>Database structure and integrity</li>
                <li>Stripe configuration and connectivity</li>
                <li>Subscription class methods and AJAX handlers</li>
                <li>Frontend functionality and real-time sync</li>
                <li>Complete user flow testing</li>
                <li>Webhook handling and integration</li>
            </ul>
        </div>
        
        <div class="test-controls">
            <h3>Test Configuration</h3>
            
            <div class="form-group">
                <label for="test-user">Test User (Business Owner):</label>
                <select id="test-user" name="test_user_id">
                    <option value="<?php echo get_current_user_id(); ?>">Current User (<?php echo wp_get_current_user()->display_name; ?>)</option>
                    <?php foreach ($business_owners as $owner): ?>
                        <option value="<?php echo $owner->ID; ?>">
                            <?php echo esc_html($owner->display_name ?: $owner->user_login); ?> 
                            (<?php echo esc_html($owner->user_email); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="quick-actions">
                <button id="run-full-test" class="button">🚀 Run Full Test Suite</button>
                <button id="run-quick-test" class="button button-secondary">⚡ Quick Health Check</button>
                <button id="test-stripe-connection" class="button button-secondary">🔗 Test Stripe Connection</button>
                <button id="clear-results" class="button button-secondary">🗑️ Clear Results</button>
            </div>
        </div>
        
        <div id="test-results" class="test-results" style="display: none;">
            <h3>Test Results</h3>
            <div id="results-content"></div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const runFullTestBtn = document.getElementById('run-full-test');
            const runQuickTestBtn = document.getElementById('run-quick-test');
            const testStripeBtn = document.getElementById('test-stripe-connection');
            const clearResultsBtn = document.getElementById('clear-results');
            const testUserSelect = document.getElementById('test-user');
            const testResults = document.getElementById('test-results');
            const resultsContent = document.getElementById('results-content');
            
            // Run full test
            runFullTestBtn.addEventListener('click', function() {
                runTest('full');
            });
            
            // Run quick test
            runQuickTestBtn.addEventListener('click', function() {
                runTest('quick');
            });
            
            // Test Stripe connection
            testStripeBtn.addEventListener('click', function() {
                testStripeConnection();
            });
            
            // Clear results
            clearResultsBtn.addEventListener('click', function() {
                testResults.style.display = 'none';
                resultsContent.innerHTML = '';
            });
            
            function runTest(type) {
                const testUserId = testUserSelect.value;
                
                // Disable buttons
                setButtonsDisabled(true);
                
                // Show loading
                testResults.style.display = 'block';
                resultsContent.innerHTML = '<div class="loading">Running ' + type + ' test suite...</div>';
                
                // Prepare form data
                const formData = new FormData();
                formData.append('run_test', '1');
                formData.append('test_type', type);
                formData.append('test_user_id', testUserId);
                formData.append('nonce', '<?php echo wp_create_nonce('subscription_test_nonce'); ?>');
                
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayResults(data.results, data.html_report);
                    } else {
                        resultsContent.innerHTML = '<div class="error-box">Test failed: ' + (data.message || 'Unknown error') + '</div>';
                    }
                })
                .catch(error => {
                    resultsContent.innerHTML = '<div class="error-box">Test failed: ' + error.message + '</div>';
                })
                .finally(() => {
                    setButtonsDisabled(false);
                });
            }
            
            function testStripeConnection() {
                setButtonsDisabled(true);
                testResults.style.display = 'block';
                resultsContent.innerHTML = '<div class="loading">Testing Stripe connection...</div>';
                
                // This would need to be implemented as a separate AJAX endpoint
                setTimeout(() => {
                    resultsContent.innerHTML = '<div class="info-box">Stripe connection test would be implemented here.</div>';
                    setButtonsDisabled(false);
                }, 1000);
            }
            
            function displayResults(results, htmlReport) {
                const summary = results.summary;
                let statusClass = 'success-box';
                let statusIcon = '✅';
                
                if (summary.failed > 0) {
                    statusClass = summary.failed > summary.passed ? 'error-box' : 'info-box';
                    statusIcon = summary.failed > summary.passed ? '❌' : '⚠️';
                }
                
                const summaryHtml = `
                    <div class="${statusClass}">
                        <h4>${statusIcon} Test Summary</h4>
                        <p><strong>Total Tests:</strong> ${summary.total}</p>
                        <p><strong>Passed:</strong> <span style="color: green;">${summary.passed}</span></p>
                        <p><strong>Failed:</strong> <span style="color: red;">${summary.failed}</span></p>
                        <p><strong>Success Rate:</strong> ${summary.success_rate}%</p>
                    </div>
                `;
                
                resultsContent.innerHTML = summaryHtml + htmlReport;
            }
            
            function setButtonsDisabled(disabled) {
                runFullTestBtn.disabled = disabled;
                runQuickTestBtn.disabled = disabled;
                testStripeBtn.disabled = disabled;
            }
        });
    </script>
</body>
</html>