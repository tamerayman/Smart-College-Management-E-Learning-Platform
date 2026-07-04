# PHP Chatbot with n8n Integration

This is a simple PHP chatbot that can be integrated with n8n for advanced workflow automation.

## Setup Instructions

1. **Deploy the PHP files** to your web server or local development environment.

2. **Configure n8n**:
   - Set up a new workflow in n8n
   - Add a Webhook node as a trigger
   - Configure the webhook to receive POST requests
   - Copy the webhook URL and update `$n8nWebhookUrl` in `ChatbotHandler.php`
   - Set up your workflow to process the incoming message and return a response

3. **Test the integration**:
   - Open `frontend.html` in your browser
   - Type a message and send it
   - The message will be processed locally if it's a simple command, or forwarded to n8n for more complex tasks

## Integration Details

### Sending Messages to n8n

When a message is received by the chatbot, it will:
1. Check if it's a simple command that can be handled locally
2. If not, forward the message to n8n via the configured webhook URL
3. Wait for n8n to process the message and return a response
4. Return the response to the user

### Receiving Responses from n8n

For more complex workflows, n8n can send responses back to the chatbot through `n8n_receiver.php`.

## Extending the Chatbot

You can extend this chatbot by:
1. Adding more simple commands in the `isSimpleCommand` and `handleLocalCommand` methods
2. Creating more complex workflows in n8n
3. Adding database functionality to store conversation history
4. Implementing user authentication
