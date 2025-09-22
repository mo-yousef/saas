<?php
/**
 * Test AJAX Endpoints with Clean Output
 * 
 * This file tests AJAX endpoints to ensure they return clean JSON
 */

// Include WordPress
require_once('wp-config.php');

if (!defined('ABSPATH')) {
    die('WordPress not loaded properly');
}

// Test company name validation endpoint
if (isset($_GET['test']) && $_GET['test'] === 'company_validation') {
    // Simulate the AJAX request
    $_POST['action'] = 'nordbooking_check_company_slug_exists';
    $_POST['company_name'] = isset($_GET['company_name']) ? $_GET['company_name'] : 'Test Company';
    
    // Clean any existing output
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Start fresh output buffering
    ob_start();
    
    try {
        $auth_instance = new \NORDBOOKING\Classes\Auth();
        $auth_instance->handle_check_company_slug_exists_ajax();
    } catch (Exception $e) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    
    exit;
}

// Test registration endpoint (dry run)
if (isset($_GET['test']) && $_GET['test'] === 'registration_dry_run') {
    // Just test if the method can be called without errors
    header('Content-Type: application/json');
    
    try {
        $auth_instance = new \NORDBOOKING\Classes\Auth();
        
        // Test if the method exists and is callable
        $reflection = new ReflectionClass($auth_instance);
        $method = $reflection->getMethod('handle_ajax_registration');
        
        echo json_encode([
            'success' => true,
            'message' => 'Registration method exists and is callable',
            'method_exists' => method_exists($auth_instance, 'handle_ajax_registration'),
            'class_loaded' => class_exists('NORDBOOKING\Classes\Auth')
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    
    exit;
}

// Default response
header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html>
<head>
    <title>AJAX Endpoint Tests</title>
</head>
<body>
    <h1>AJAX Endpoint Tests</h1>
    
    <h2>Available Tests:</h2>
    <ul>
        <li><a href="?test=company_validation&company_name=Test%20Company">Test Company Name Validation</a></li>
        <li><a href="?test=registration_dry_run">Test Registration Method (Dry Run)</a></li>
    </ul>
    
    <h2>Manual Test:</h2>
    <form>
        <label>Company Name:</label>
        <input type="text" id="company_name" value="Test Company">
        <button type="button" onclick="testCompanyValidation()">Test Validation</button>
    </form>
    
    <div id="result" style="margin-top: 20px; padding: 10px; border: 1px solid #ccc; background: #f9f9f9;"></div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    function testCompanyValidation() {
        const companyName = document.getElementById('company_name').value;
        const resultDiv = document.getElementById('result');
        
        resultDiv.innerHTML = '<p>Testing company name validation...</p>';
        
        fetch('?test=company_validation&company_name=' + encodeURIComponent(companyName))
            .then(response => response.text())
            .then(text => {
                resultDiv.innerHTML = '<h3>Raw Response:</h3><pre>' + text + '</pre>';
                
                try {
                    const json = JSON.parse(text);
                    resultDiv.innerHTML += '<h3>Parsed JSON:</h3><pre>' + JSON.stringify(json, null, 2) + '</pre>';
                } catch (e) {
                    resultDiv.innerHTML += '<h3>JSON Parse Error:</h3><p>' + e.message + '</p>';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '<h3>Fetch Error:</h3><p>' + error.message + '</p>';
            });
    }
    </script>
</body>
</html>