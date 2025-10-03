<?php
/**
 * Custom Registration Endpoint
 * 
 * This is a workaround for the AJAX 403 issue
 */

// Include WordPress
require_once('../../../wp-load.php');

// Set proper headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'data' => ['message' => 'Method not allowed']]);
    exit;
}

// Log the request
error_log('NORDBOOKING: Custom registration endpoint called');
error_log('NORDBOOKING: POST data: ' . print_r($_POST, true));

try {
    // Check if Auth class exists
    if (!class_exists('NORDBOOKING\Classes\Auth')) {
        throw new Exception('Auth class not found');
    }
    
    // Create Auth instance and call the registration handler
    $auth = new NORDBOOKING\Classes\Auth();
    
    if (!method_exists($auth, 'handle_ajax_registration')) {
        throw new Exception('Registration handler method not found');
    }
    
    // Call the registration handler
    $auth->handle_ajax_registration();
    
} catch (Exception $e) {
    error_log('NORDBOOKING: Custom endpoint error: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'data' => [
            'message' => 'Registration failed: ' . $e->getMessage()
        ]
    ]);
}
?>