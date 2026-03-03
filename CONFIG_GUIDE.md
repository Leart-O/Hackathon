# Configuration Guide

## Getting Your Hugging Face Token

### Step-by-Step:

1. **Create Account** (if you don't have one)
   - Visit https://huggingface.co/
   - Click "Sign Up"
   - Complete registration

2. **Generate API Token**
   - Go to https://huggingface.co/settings/tokens
   - Click "New token"
   - Give it a name (e.g., "Hackathon")
   - Select "Read" access level (you only need this)
   - Click "Create token"
   - Copy the token (save it somewhere safe!)

3. **Token Format**
   - Your token will look like: `hf_xxxxxxxxxxxxxxxxxxxxxxxxxxxxx`
   - Keep it confidential!

## Setting Up in Your App

### Method 1: Via Web Interface (Recommended)

1. Log into your Hackathon app
2. Click "Settings" button (⚙️ gear icon)
3. Paste your token in the "API Token" field
4. Click "Save Token"
5. (Optional) Change the model to your preference
6. Click "Save Model"

### Method 2: Via Database (Direct)

```sql
UPDATE users SET huggingface_token = 'hf_xxxxxxxxxxxxxxxxxxxxxxxxxxxxx' WHERE id = 1;
UPDATE users SET huggingface_model = 'Qwen/Qwen2.5-7B-Instruct' WHERE id = 1;
```

## Choosing a Model

### Popular Models:

**For General Chat (Recommended):**
```
Qwen/Qwen2.5-7B-Instruct
```
- Fast responses
- Good quality
- Multilingual support

**For Long Responses:**
```
meta-llama/Llama-2-7b-chat
```
- Detailed answers
- Better context understanding
- Slightly slower

**For Speed (Lightweight):**
```
mistralai/Mistral-7B-Instruct-v0.2
```
- Very fast
- Good quality
- Lower latency

**For Simple Tasks:**
```
gpt2
```
- Fastest
- Lightweight
- Good for testing

### Browse More Models:
https://huggingface.co/models?pipeline_tag=text-generation

## Troubleshooting

### Issue: "Token not configured"
**Solution:**
- Check that you're logged in
- Go to Settings and save your token again
- Make sure you ran `migrate.php`

### Issue: "Invalid token"
**Solution:**
- Get a new token from https://huggingface.co/settings/tokens
- Make sure it has "Read" access
- Copy it exactly (no extra spaces)

### Issue: "API Error (HTTP 401)"
**Solution:**
- Your token is invalid or expired
- Get a new token from Hugging Face
- Update it in Settings

### Issue: "Slow Responses"
**Solution:**
- Try a faster model (mistralai, gpt2)
- Some models take longer to generate responses
- Check Hugging Face server status

### Issue: "Model not found"
**Solution:**
- Check the model name is correct
- Visit https://huggingface.co/models to verify
- Default model: `Qwen/Qwen2.5-7B-Instruct`

## Testing Your Setup

1. **Test Token in Settings:**
   - Go to Settings
   - Try a simple model like `gpt2`
   - Make a short request

2. **Check API Status:**
   - Visit https://huggingface.co/status
   - Ensure inference API is running

3. **Review Logs:**
   - Check `api_debug.log` for errors
   - PHP error logs may also contain useful info

## API Quotas & Limits

- **Free Tier:** Limited requests per month
- **Check Usage:** https://huggingface.co/app/billing
- **Upgrade:** Add payment method for higher limits
- **Rate Limits:** May apply per model

## Security Best Practices

1. ✅ Never share your token
2. ✅ Keep token private (don't commit to git)
3. ✅ Regenerate token if compromised
4. ✅ Use "Read" access only (not "Write")
5. ✅ Consider token rotation periodically

## Advanced Configuration

### Custom Models:

Any model on Hugging Face can be used if it supports chat:

```php
// In settings.php form
<input name="huggingface_model" value="your-custom-model-name" />
```

### Model Selection Tips:

- Instruction-tuned models work best (usually have "Instruct" in name)
- Larger models (7B+) are more capable but slower
- Smaller models (3B-7B) are faster but less capable
- Test different models to find your sweet spot

## Environment Variables (Optional)

For production, consider using environment variables:

```php
// In huggingface_api.php (future improvement)
$token = $_ENV['HF_TOKEN'] ?? $user['huggingface_token'];
```

Set in `.env` file:
```
HF_TOKEN=your_token_here
HF_MODEL=your_model_here
```

---

**Setup Complete!** You're ready to use Hugging Face with your Hackathon app. 🚀

For more help, visit:
- Hugging Face Docs: https://huggingface.co/docs
- API Documentation: https://huggingface.co/docs/api-inference
- Community Forum: https://discuss.huggingface.co/
