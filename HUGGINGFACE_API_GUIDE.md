# Hugging Face Inference API Integration Guide

## Overview
Your project now uses the **Hugging Face Inference API** instead of Together.ai. The Hugging Face free tier provides access to thousands of models without requiring a credit card.

---

## API Endpoint Format

**Base Endpoint:** `https://api-inference.huggingface.co/models/{model-id}`

**Authentication:** Bearer token in `Authorization` header

**Example:**
```
POST https://api-inference.huggingface.co/models/gpt2
Authorization: Bearer hf_xxxxxxxxxxxxxxxxxxxxxx
Content-Type: application/json
```

---

## Getting Your API Token

1. Go to https://huggingface.co/settings/tokens
2. Click "New token" 
3. Name it (e.g., "Apollo AI Project")
4. Select "Read" access level
5. Copy the token
6. Visit your project's **Settings** page and paste the token

**Important:** Keep your token private. Do not commit it to git or share it publicly.

---

## Payload Format

The Hugging Face Inference API uses a **different format** than OpenAI-compatible APIs:

### Request Format
```json
{
  "inputs": "Your text prompt here",
  "parameters": {
    "max_new_tokens": 500,
    "temperature": 0.7,
    "do_sample": true
  }
}
```

**Key differences from OpenAI format:**
- Uses `"inputs"` key (not `"messages"` array)
- Takes plain text, not chat format
- `"parameters"` is a single object, not top-level fields

### Response Format
The API returns an **array** of objects:

```json
[
  {
    "generated_text": "Your text prompt here...and the generated continuation here"
  }
]
```

**Note:** The response includes your original prompt + the generated text. The code automatically strips the prompt.

---

## PHP Implementation

### Using the Built-in Function

Your project includes a `callHuggingFaceAPI()` function in `huggingface_api.php`:

```php
require 'db.php';
require 'huggingface_api.php';

try {
    $messages = [
        ['role' => 'user', 'content' => 'What is machine learning?']
    ];
    
    $response = callHuggingFaceAPI($messages, $user_id, $db, 500);
    echo "Response: " . $response;
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

**Function Signature:**
```php
callHuggingFaceAPI($messages, $user_id, $db, $max_tokens = 500)
```

**Parameters:**
- `$messages` (array): Array of message objects with 'role' and 'content' keys
- `$user_id` (int): Current user's ID (to fetch their token from database)
- `$db` (PDO): Database connection
- `$max_tokens` (int): Maximum tokens to generate (default: 500)

**Returns:** String containing the generated text

**Throws:** Exception on API errors with helpful error messages

---

## cURL Examples

### Basic cURL Request (bash/command line)

```bash
curl -X POST \
  "https://api-inference.huggingface.co/models/gpt2" \
  -H "Authorization: Bearer YOUR_HF_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "inputs": "Tell me a story about",
    "parameters": {
      "max_new_tokens": 100,
      "temperature": 0.7,
      "do_sample": true
    }
  }'
```

### cURL in PHP (without using the built-in function)

```php
<?php
$token = "hf_xxxxxxxxxxxxx"; // Your Hugging Face token
$model = "gpt2";
$prompt = "Tell me a story about";

$endpoint = "https://api-inference.huggingface.co/models/" . urlencode($model);

$payload = json_encode([
    "inputs" => $prompt,
    "parameters" => [
        "max_new_tokens" => 100,
        "temperature" => 0.7,
        "do_sample" => true
    ]
]);

$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token  // Token goes here in the header
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_TIMEOUT, 120);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode >= 400) {
    echo "Error: HTTP " . $httpCode;
    echo "\nResponse: " . $response;
} else {
    $data = json_decode($response, true);
    if (isset($data[0]['generated_text'])) {
        echo "Generated: " . $data[0]['generated_text'];
    }
}
?>
```

---

## Recommended Models

These models work well on Hugging Face's free tier:

### Lightweight & Fast
- **gpt2** - Fast, good for quick responses
- **distilgpt2** - Even faster, smaller model

### Better Quality
- **mistralai/Mistral-7B** - Powerful, still reasonable speed
- **Qwen/Qwen2.5-7B-Instruct** - Excellent quality, great instruction following
- **meta-llama/Llama-2-7b-chat** - Good chat model

### Domain-Specific
- **EleutherAI/gpt-neox-20b** - Large, powerful model

To find more: https://huggingface.co/models?pipeline_tag=text-generation

---

## Error Handling

The `callHuggingFaceAPI()` function provides helpful error messages for common issues:

### HTTP 401 - Invalid Token
```
API Error (HTTP 401): Invalid or expired Hugging Face token. 
Please check your token at Settings. 
Get a new token at https://huggingface.co/settings/tokens
```
**Solution:** Verify your token is correct and hasn't expired.

### HTTP 404 - Model Not Found
```
API Error (HTTP 404): Model 'invalid-model' not found on Hugging Face. 
Visit https://huggingface.co/models?pipeline_tag=text-generation to find available models.
```
**Solution:** Check the model name at https://huggingface.co/models

### HTTP 503 - Model Loading
```
API Error (HTTP 503): Model is loading or Hugging Face API is temporarily unavailable. 
Please try again in a few moments.
```
**Solution:** Models sometimes need to be loaded. Wait a few seconds and try again. First request to a model can take longer.

### HTTP 429 - Rate Limited
```
API Error (HTTP 429): Rate limit exceeded. 
Please wait before making another request.
```
**Solution:** Wait a few seconds before making another request. Free tier has rate limits.

### HTTP 500 - Server Error
```
API Error (HTTP 500): Hugging Face server error. 
Please try again later.
```
**Solution:** The Hugging Face API is experiencing issues. Try again later.

---

## Debugging

### View API Logs
Debug logs are saved to `api_debug.log` in your project root:

```php
// The logs include:
// - Endpoint used
// - HTTP response code  
// - First 500 characters of response
// - Model name
// - Prompt (first 100 characters)
```

View the log:
```bash
tail -f api_debug.log
```

### Manual Testing

Create a test file `test_hf_api.php`:

```php
<?php
require 'db.php';
require 'huggingface_api.php';

session_start();
$_SESSION['user_id'] = 1; // Replace with actual user ID

try {
    $messages = [
        ['role' => 'user', 'content' => 'What is AI?']
    ];
    
    $result = callHuggingFaceAPI($messages, 1, $db, 100);
    echo "Success! Response:\n";
    echo $result;
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    echo "\n\nCheck api_debug.log for details.";
}
?>
```

Run from command line:
```bash
php test_hf_api.php
```

---

## Differences from Together.ai

| Feature | Together.ai | Hugging Face |
|---------|------------|--------------|
| **Endpoint** | `api.together.xyz/v1/chat/completions` | `api-inference.huggingface.co/models/{model}` |
| **Payload Format** | OpenAI-compatible (messages array) | Simple text input ("inputs" key) |
| **Response Format** | `choices[0].message.content` | Array: `[0].generated_text` |
| **Requires Credit Card** | Yes | No (free tier) |
| **Response Includes Prompt** | No | Yes (you must strip it) |
| **Model Format** | Model ID only | Full model path (user/model) |

---

## Files Modified

- **huggingface_api.php** - Updated to use Hugging Face Inference API
- **settings.php** - Updated labels to reference Hugging Face
- **migrate.php** - Updated default model to "gpt2" and messages
- **main.php** - Uses `callHuggingFaceAPI()` (no changes needed)
- **flashcards.php** - Uses `callHuggingFaceAPI()` (no changes needed)
- **quiz.php** - Uses `callHuggingFaceAPI()` (no changes needed)

---

## Quick Start

1. **Get your token:** Visit https://huggingface.co/settings/tokens and create a new token
2. **Add to project:** Go to your project's Settings page and paste the token
3. **Verify connection:** Check that you can generate text in Chat, Quiz, or Flashcards
4. **Check logs:** If there are errors, review `api_debug.log` for details
5. **Change model:** Update the model in Settings if you want to try different ones

---

## Support & Resources

- **Hugging Face Docs:** https://huggingface.co/docs/api-inference/en/
- **Model Browser:** https://huggingface.co/models
- **API Status:** https://status.huggingface.co/
- **Community:** https://huggingface.co/discuss

Happy coding! 🚀
