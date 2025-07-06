<?php
/**
 * API endpoint for testing AI services
 */
session_start();
require_once('../db_connection.php');
require_once('../includes/functions.php');
require_once('../includes/ai_functions.php');

// Check if user is logged in as admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get the request body
$request_body = file_get_contents('php://input');
$data = json_decode($request_body, true);

// Check if prompt is provided
if (!isset($data['prompt']) || empty($data['prompt'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Prompt is required']);
    exit;
}

// Sanitize the prompt
$prompt = strip_tags($data['prompt']);

// Log the test request
ai_debug_log("Testing AI service", [
    'service' => $ai_config['service'],
    'prompt' => $prompt
]);

// Call Grok 3 API
$response = null;
if ($ai_config['service'] === 'grok') {
    $response = call_grok_api($prompt);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Only Grok 3 is supported. Current service: ' . $ai_config['service']]);
    exit;
}

// Check if we got a response
if ($response === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to get response from AI service']);
    exit;
}

// Return the response
echo json_encode([
    'success' => true,
    'service' => $ai_config['service'],
    'response' => $response
]);
