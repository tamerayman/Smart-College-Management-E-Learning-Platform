<?php
require_once 'ChatbotHandler.php';

header('Content-Type: application/json');

// Get the incoming request
$input = json_decode(file_get_contents('php://input'), true);

// Initialize the chatbot handler
$chatbot = new ChatbotHandler();

// Process the incoming message
$response = $chatbot->processMessage($input);

// Return the response
echo json_encode($response);
?>
