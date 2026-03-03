# Hugging Face API Integration - Setup Instructions

Your Hackathon application has been updated to use Hugging Face API instead of OpenRouter. Follow these steps to get started:

## Step 1: Create a Hugging Face Account and Get Your Token

1. Visit [Hugging Face](https://huggingface.co/) and sign up for a free account
2. Go to [Settings → API Tokens](https://huggingface.co/settings/tokens)
3. Click "New token" and create an API token with **Read** access
4. Copy your token (you'll need it in the next step)

## Step 2: Run the Database Migration

1. Open your browser and go to: `http://localhost/Hackathon/migrate.php`
2. This will add two new columns to your database:
   - `huggingface_token` - stores your API token
   - `huggingface_model` - stores your selected model

## Step 3: Add Your Token to Settings

1. Log in to your Hackathon application
2. Click the **Settings** button (gear icon) in the top right
3. Paste your Hugging Face API token in the "API Token" field
4. Click "Save Token"

## Step 4: Choose Your AI Model (Optional)

In the Settings page, you can also choose which Hugging Face model to use. Popular options include:

- **Qwen/Qwen2.5-7B-Instruct** (Recommended - Good balance of quality and speed)
- **mistralai/Mistral-7B-Instruct-v0.2** (Fast and reliable)
- **meta-llama/Llama-2-7b-chat** (Good for creative tasks)
- **gpt2** (Lightweight, fast for simple tasks)

You can browse all available models at: https://huggingface.co/models?pipeline_tag=text-generation

## What Changed?

### Updated Files:
- **main.php** - Now uses Hugging Face API for chat
- **flashcards.php** - Uses Hugging Face to generate flashcards
- **quiz.php** - Uses Hugging Face to generate quizzes
- **index.php** - Prompts users to log in for API features
- **huggingface_api.php** (NEW) - Helper function for all API calls
- **settings.php** (NEW) - Settings page for managing API tokens and models
- **migrate.php** (NEW) - Database migration script

### How It Works:
1. When you make a request (chat, flashcards, quiz), the app retrieves your token from the database
2. It sends the request to Hugging Face's Inference API
3. The response is processed and displayed in your application

## Troubleshooting

- **"Token not configured" error**: Make sure you've saved your token in Settings
- **API errors**: Check that your Hugging Face token is valid and has Read access
- **Slow responses**: Some models are slower than others. Try a different model in Settings
- **Token invalid**: Visit your [Hugging Face API Tokens page](https://huggingface.co/settings/tokens) to verify your token is active

## Notes

- Your token is stored encrypted in the database (per user)
- Each user can have their own token and model preference
- Hugging Face has free API quota - check your usage at https://huggingface.co/app/billing
- You can change your token and model anytime in Settings

Enjoy using Hugging Face with your Hackathon app!
