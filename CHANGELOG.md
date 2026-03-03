# Changelog - Switched from Together.ai to Hugging Face Inference API

## Changes Made

### 1. **huggingface_api.php** (Core API Integration)
- ✅ Switched from Together.ai endpoint (`api.together.xyz/v1/chat/completions`) to Hugging Face Inference API (`api-inference.huggingface.co/models/{model-id}`)
- ✅ Updated payload format from OpenAI-compatible to Hugging Face format:
  - Changed from: `{"model": "...", "messages": [...], "max_tokens": 500}`
  - Changed to: `{"inputs": "prompt", "parameters": {"max_new_tokens": 500, ...}}`
- ✅ Updated response parsing:
  - Changed from: `$data['choices'][0]['message']['content']`
  - Changed to: `$data[0]['generated_text']`
- ✅ Enhanced error messages with helpful troubleshooting links
- ✅ Added automatic prompt stripping (HF returns prompt + response together)
- ✅ Updated all comments to reference Hugging Face instead of Together.ai
- ✅ Added SSL verification for security
- ✅ Improved logging with more detailed debug information

### 2. **settings.php** (Configuration Page)
- ✅ Updated success message: "Together.ai API key updated successfully!" → "Hugging Face API token updated successfully!"
- ✅ Updated label: "Together.ai API key" to reflect Hugging Face
- ✅ Updated link to token page: https://huggingface.co/settings/tokens
- ✅ Updated model field instructions with Hugging Face-specific model recommendations
- ✅ Updated model link to: https://huggingface.co/models?pipeline_tag=text-generation

### 3. **migrate.php** (Database Migrations)
- ✅ Changed default model from `meta-llama/Llama-2-7b-chat-hf` to `gpt2` (more reliable on free tier)
- ✅ Updated message from "Together.ai API key" to "Hugging Face API token"

### 4. **New Documentation Files**
- ✅ Created **HUGGINGFACE_API_GUIDE.md** - Comprehensive guide with:
  - API endpoint format and examples
  - Instructions for getting a Hugging Face token
  - Payload and response format explanations
  - PHP usage examples
  - cURL examples for testing
  - Recommended models list
  - Error handling guide
  - Debugging instructions
  - Differences from Together.ai
  
- ✅ Created **test_huggingface.php** - Test script to verify setup:
  - Checks database connection
  - Verifies token configuration
  - Tests API call
  - Shows helpful error messages if issues occur

### 5. **No Changes Required In**
- ✅ main.php - Uses `callHuggingFaceAPI()` function (unchanged interface)
- ✅ flashcards.php - Uses `callHuggingFaceAPI()` function (unchanged interface)
- ✅ quiz.php - Uses `callHuggingFaceAPI()` function (unchanged interface)
- ✅ All other files - Not affected

---

## Why This Change Was Made

**Together.ai Issues:**
- ❌ Requires a valid credit card to access API (even for free tier)
- ❌ User couldn't obtain an API key without credit card

**Hugging Face Advantages:**
- ✅ Free tier works without credit card
- ✅ Thousands of models available
- ✅ Excellent documentation
- ✅ Community-driven model selection
- ✅ Simple, straightforward API
- ✅ No billing concerns

---

## API Comparison

| Feature | Together.ai | Hugging Face |
|---------|------------|--------------|
| **Credit Card Required** | Yes ❌ | No ✅ |
| **Free Tier Access** | Limited | Full ✅ |
| **API Format** | OpenAI-compatible | Inference-specific |
| **Model Format** | Model ID | Full model path |
| **Response Parsing** | `choices[0].message.content` | `[0].generated_text` |
| **Endpoint** | `api.together.xyz/v1/...` | `api-inference.huggingface.co/...` |

---

## Migration Steps for Users

If you're upgrading from the Together.ai version:

1. **Get your Hugging Face token:**
   - Visit https://huggingface.co/settings/tokens
   - Create a new token (select "Read" access)
   - Copy the token

2. **Update your project:**
   - Go to Settings page
   - Paste your Hugging Face token
   - (Optional) Change the model if desired

3. **Test the connection:**
   - Run: `php test_huggingface.php`
   - Or try generating text in Chat/Quiz/Flashcards

4. **Check logs if issues occur:**
   - Look at `api_debug.log` for error details
   - Refer to **HUGGINGFACE_API_GUIDE.md** for troubleshooting

---

## Default Model Information

**Current Default:** `gpt2`

Why gpt2?
- ✅ Most reliable on Hugging Face free tier
- ✅ Fast response times
- ✅ Never shows "Model Loading" errors
- ✅ Works consistently

**To change the model:**
1. Go to Settings page
2. Enter a different model ID (e.g., `mistralai/Mistral-7B`)
3. Save

**Popular alternatives:**
- `mistralai/Mistral-7B` - Better quality
- `Qwen/Qwen2.5-7B-Instruct` - Excellent instruction following
- `meta-llama/Llama-2-7b-chat` - Good chat model

Find more at: https://huggingface.co/models?pipeline_tag=text-generation

---

## Testing & Verification

### Quick Test (Command Line)
```bash
php test_huggingface.php
```

### Manual cURL Test
```bash
curl -X POST \
  "https://api-inference.huggingface.co/models/gpt2" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{"inputs": "Hello, world!"}' 
```

### Debug Logs
```bash
tail -f api_debug.log
```

---

## Rollback Information

If you need to go back to Together.ai (unlikely, given the credit card requirement):

1. Keep the database columns as-is (`huggingface_token`, `huggingface_model` can store Together.ai data)
2. Revert `huggingface_api.php` to use Together.ai endpoint
3. Update payload format back to OpenAI-compatible
4. Update response parsing back to `choices[0].message.content`

But we recommend staying with Hugging Face! No credit card needed. ✅

---

## Support

For detailed information:
- Read **HUGGINGFACE_API_GUIDE.md** for full documentation
- Run **test_huggingface.php** to diagnose issues
- Check **api_debug.log** for error details
- Visit https://huggingface.co/docs/api-inference/ for API reference

Happy coding! 🚀
