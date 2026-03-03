<?php
/**
 * Call Hugging Face Inference Providers Router API
 * 
 * Endpoint: https://router.huggingface.co/v1/chat/completions
 * 
 * This uses the OpenAI-compatible chat completions endpoint from Hugging Face Router.
 * The router automatically selects the best available provider for your model.
 * 
 * Authentication: Bearer token from https://huggingface.co/settings/tokens
 * 
 * Updated: Using new router endpoint (api-inference.huggingface.co is deprecated)
 */
function callHuggingFaceAPI($messages, $user_id, $db, $max_tokens = 500) {
    // Fetch user's token and model
    $stmt = $db->prepare("SELECT huggingface_token, huggingface_model FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $token = $user['huggingface_token'] ?? null;
    $model = $user['huggingface_model'] ?? 'mistralai/Mistral-7B-Instruct-v0.2';
    
    if (!$token) {
        throw new Exception('Hugging Face API key not configured. Please visit settings to add your API key from https://huggingface.co/settings/tokens');
    }
    
    if (empty($messages)) {
        throw new Exception('No messages provided');
    }
    
    // Hugging Face Router API endpoint - uses OpenAI-compatible chat completions format
    $endpoint = "https://router.huggingface.co/v1/chat/completions";
    
    // Prepare payload in OpenAI-compatible format
    // Router will automatically select the best provider for this model
    $payload = [
        'model' => $model,
        'messages' => $messages,
        'max_tokens' => $max_tokens,
        'temperature' => 0.7,
        'stream' => false
    ];
    
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // Log the request for debugging
    file_put_contents('api_debug.log', "=== Hugging Face Router API Request ===\n", FILE_APPEND);
    file_put_contents('api_debug.log', "Endpoint: $endpoint\n", FILE_APPEND);
    file_put_contents('api_debug.log', "Model: $model\n", FILE_APPEND);
    file_put_contents('api_debug.log', "HTTP Code: $httpCode\n", FILE_APPEND);
    file_put_contents('api_debug.log', "Response: " . substr($response, 0, 500) . "\n\n", FILE_APPEND);
    
    if ($curlError) {
        throw new Exception("cURL error: $curlError");
    }
    
    // Decode the JSON response
    $data = json_decode($response, true);
    
    if ($httpCode >= 400) {
        // Parse error messages
        $errorMsg = "Unknown error";
        
        if (is_array($data)) {
            if (isset($data['error'])) {
                if (is_array($data['error'])) {
                    $errorMsg = $data['error']['message'] ?? json_encode($data['error']);
                } else {
                    $errorMsg = $data['error'];
                }
            } elseif (isset($data['message'])) {
                $errorMsg = $data['message'];
            }
        }
        
        // Provide helpful error messages for common HTTP codes
        if ($httpCode === 401) {
            $errorMsg = "Invalid or expired Hugging Face token. Get a new token at https://huggingface.co/settings/tokens";
        } elseif ($httpCode === 404) {
            $errorMsg = "Model '$model' not found. Visit https://huggingface.co/models?pipeline_tag=text-generation";
        } elseif ($httpCode === 503) {
            $errorMsg = "Model is loading or service temporarily unavailable. Please try again in a moment.";
        } elseif ($httpCode === 429) {
            $errorMsg = "Rate limit exceeded. Please wait before making another request.";
        }
        
        throw new Exception("API Error (HTTP $httpCode): " . $errorMsg);
    }
    
    // OpenAI-compatible response format
    // Response: {"choices": [{"message": {"content": "..."}}]}
    if (is_array($data) && isset($data['choices']) && count($data['choices']) > 0) {
        $choice = $data['choices'][0];
        if (isset($choice['message']['content'])) {
            return trim($choice['message']['content']);
        }
    }
    
    // Log unexpected response format for debugging
    file_put_contents('api_debug.log', "Unexpected response format: " . json_encode($data) . "\n\n", FILE_APPEND);
    throw new Exception("Unexpected API response format from Hugging Face. Please check api_debug.log for details.");
}
?>
