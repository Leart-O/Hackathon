# Troubleshooting: HTTP 404 Error

## What This Error Means

The **HTTP 404 error** means the API endpoint cannot find the resource you're requesting. This usually happens because:

1. **Wrong model name** - The model name doesn't exist or is invalid
2. **Model not available** - The model exists but isn't available in Hugging Face Inference API
3. **Invalid endpoint** - The API path is incorrect

## Solutions

### Solution 1: Verify Your Model Name (MOST COMMON)

1. Go to Settings → Model Selection
2. Copy your current model name (e.g., `Qwen/Qwen2.5-7B-Instruct`)
3. Visit https://huggingface.co/models
4. Search for your model to verify it exists
5. Make sure the name matches exactly (case-sensitive!)

### Solution 2: Use a Verified Working Model

Try one of these models that are guaranteed to work:

**Easy Option (Recommended):**
```
meta-llama/Llama-2-7b-chat-hf
```

**Fast Option:**
```
mistralai/Mistral-7B-Instruct-v0.1
```

**Lightweight Option:**
```
gpt2
```

Steps:
1. Go to Settings
2. Change Model ID to one of the above
3. Click "Save Model"
4. Try your request again

### Solution 3: Check if Model Supports Chat API

Not all models support the chat completions format. Look for models with these keywords:
- **"Instruct"** - Instruction-tuned models
- **"Chat"** - Chat-optimized models
- **"Conversation"** - Conversational models

❌ Avoid:
- Base models without tuning (e.g., plain "gpt2" won't work)
- Models not marked as instruction-tuned

✅ Use:
- Any model with "Instruct" or "Chat" in the name
- Models marked as text-generation

### Solution 4: Verify Your Token

Your API token might be invalid:

1. Visit https://huggingface.co/settings/tokens
2. Check if your token is active (not revoked/expired)
3. Create a new token if needed:
   - Click "New token"
   - Name: "Hackathon"
   - Access: "Read"
   - Click "Create token"
4. Copy the new token
5. Go to Settings → API Token
6. Paste the new token
7. Click "Save Token"

### Solution 5: Check API Status

Sometimes Hugging Face API is temporarily unavailable:

1. Visit https://huggingface.co/status
2. Check if "Inference API" shows "Operational"
3. If not operational, wait a few minutes and try again

## Recommended Setup

**Best Configuration (Guaranteed to Work):**

1. Model: `meta-llama/Llama-2-7b-chat-hf`
2. Valid Hugging Face token (from https://huggingface.co/settings/tokens)
3. Read access permission on token

**To Test:**

1. Update model in Settings
2. Try a simple test: "Hello, how are you?"
3. Check api_debug.log for detailed error messages

## Debug Information

To see detailed error logs:

1. Open file: `api_debug.log`
2. Look for the latest entry
3. Check:
   - Model name being used
   - HTTP Code
   - Response message

## Still Not Working?

If none of the above works:

1. **Clear browser cache** - Settings might be cached
2. **Run migration again** - Open http://localhost/Hackathon/migrate.php
3. **Check PHP logs** - Review Apache/PHP error logs
4. **Try a different model** - Some models have limited availability
5. **Contact Hugging Face** - Check their status page or community

## Common Model Names That Work

```
meta-llama/Llama-2-7b-chat-hf
mistralai/Mistral-7B-Instruct-v0.1
NousResearch/Nous-Hermes-2-Mixtral-8x7B-DPO
teknium/OpenHermes-2.5-Mistral-7B
gpt2 (basic, but works)
```

Visit https://huggingface.co/models?pipeline_tag=text-generation&sort=trending for more options.

---

**Pro Tip:** Start with `meta-llama/Llama-2-7b-chat-hf` - it's reliable and widely available! ✅
