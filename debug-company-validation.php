<?php
/**
 * Debug Company Name Validation
 * 
 * Simple test endpoint to debug company name validation issues
 */

// Include WordPress
require_once('wp-config.php');

if (!defined('ABSPATH')) {
    die('WordPress not loaded properly');
}

// Set JSON header
header('Content-Type: application/json');

try {
    // Test company name validation
    $test_company_name = isset($_GET['company_name']) ? sanitize_text_field($_GET['company_name']) : 'Test Company';
    
    $auth_instance = new \NORDBOOKING\Classes\Auth();
    
    // Use reflection to access private method
    $reflection = new ReflectionClass($auth_instance);
    $method = $reflection->getMethod('get_user_id_by_company_name');
    $method->setAccessible(true);
    
    $existing_user_id = $method->invoke($auth_instance, $test_company_name);
    
    $response = [
        'success' => true,
        'company_name' => $test_company_name,
        'existing_user_id' => $existing_user_id,
        'is_available' => $existing_user_id === null,
        'message' => $existing_user_id === null ? 'Company name is available' : 'Company name is taken'
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    $error_response = [
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ];
    
    echo json_encode($error_response, JSON_PRETTY_PRINT);
}
?>