<?php
header('Content-Type: application/json');

// Get the incoming response from n8n
$input = json_decode(file_get_contents('php://input'), true);

// Validate the response
if (!isset($input['response'])) {
    $response = [
        'status' => 'error',
        'message' => 'Invalid response format'
    ];
    echo json_encode($response);
    exit;
}

// Process the response
$message = $input['response'];
$userId = isset($input['userId']) ? $input['userId'] : 'system';

// Here you could store the message in a database or perform other actions

// Return confirmation to n8n
$response = [
    'status' => 'success',
    'message' => 'Response received and processed'
];

echo json_encode($response);
?>
