<?php
class ChatbotHandler {
    private $n8nWebhookUrl = 'http://your-n8n-instance/webhook-endpoint';
    
    public function processMessage($input) {
        // Extract message from input
        $message = isset($input['message']) ? $input['message'] : '';
        $userId = isset($input['userId']) ? $input['userId'] : 'anonymous';
        
        // Basic validation
        if (empty($message)) {
            return $this->createResponse('Please send a message.');
        }
        
        // Process message locally or send to n8n
        if ($this->isSimpleCommand($message)) {
            return $this->handleLocalCommand($message);
        } else {
            return $this->forwardToN8n($message, $userId);
        }
    }
    
    private function isSimpleCommand($message) {
        // Simple commands that don't require n8n processing
        $simpleCommands = ['hello', 'hi', 'help'];
        return in_array(strtolower($message), $simpleCommands);
    }
    
    private function handleLocalCommand($message) {
        $message = strtolower($message);
        
        switch ($message) {
            case 'hello':
            case 'hi':
                return $this->createResponse('Hello! How can I help you today?');
            case 'help':
                return $this->createResponse('You can ask me questions or give me tasks to perform.');
            default:
                return $this->createResponse('I did not understand that command.');
        }
    }
    
    private function forwardToN8n($message, $userId) {
        $payload = [
            'message' => $message,
            'userId' => $userId,
            'timestamp' => time()
        ];
        
        try {
            $response = $this->makeHttpRequest($this->n8nWebhookUrl, $payload);
            return json_decode($response, true) ?: $this->createResponse('Sorry, I encountered an error processing your request.');
        } catch (Exception $e) {
            return $this->createResponse('Error communicating with the server: ' . $e->getMessage());
        }
    }
    
    private function makeHttpRequest($url, $payload) {
        $ch = curl_init($url);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
        }
        
        curl_close($ch);
        return $response;
    }
    
    private function createResponse($message) {
        return [
            'response' => $message,
            'timestamp' => time()
        ];
    }
}
?>
