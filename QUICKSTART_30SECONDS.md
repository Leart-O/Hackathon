# ⚡ Quick Start Guide - Hugging Face Inference API

## In 30 Seconds

1. **Get Token** → https://huggingface.co/settings/tokens (click "New token")
2. **Add to Project** → Settings page → paste token → Save
3. **Verify** → Run `php test_huggingface.php`
4. **Done!** → Use Chat/Quiz/Flashcards

---

## The Three Most Important URLs

1. **Get Token Here:** https://huggingface.co/settings/tokens
2. **Find Models Here:** https://huggingface.co/models?pipeline_tag=text-generation
3. **API Docs:** https://huggingface.co/docs/api-inference/

---

## The Command to Test

```bash
php test_huggingface.php
```

Should show: `✓ All tests passed!`

---

## If Something Goes Wrong

1. Check `api_debug.log` for error details
2. Read `HUGGINGFACE_API_GUIDE.md` for solutions
3. Run `php test_huggingface.php` again to diagnose

---

## Best Models to Try

**Fast (Default):**
```
gpt2
```

**Better Quality:**
```
mistralai/Mistral-7B
Qwen/Qwen2.5-7B-Instruct
meta-llama/Llama-2-7b-chat
```

Change in: Settings page → Model Selection

---

## How It Works

```
Your App
   ↓
PHP Code (callHuggingFaceAPI function)
   ↓
Hugging Face API
   ↓
AI Model Response
   ↓
Back to Your App
```

---

## Important Facts

✅ **No Credit Card Required** - Free tier works without payment
✅ **Token Required** - Get from https://huggingface.co/settings/tokens
✅ **First Request Slower** - Models take 10-30 seconds to load first time
✅ **Rate Limits** - Free tier has limits, wait between requests if needed
✅ **Logs Available** - Check `api_debug.log` for troubleshooting

---

## Files You Care About

| File | Purpose |
|------|---------|
| `settings.php` | Add your token here |
| `test_huggingface.php` | Run to verify setup |
| `HUGGINGFACE_API_GUIDE.md` | Full documentation |
| `api_debug.log` | Error details for debugging |

---

## The One Command You Need

```bash
php test_huggingface.php
```

Run this to verify everything works.

---

**Questions?** Read `HUGGINGFACE_API_GUIDE.md` for detailed answers.
