<?php
/**
 * QUICK START GUIDE
 * 
 * 1. Get your Hugging Face token:
 *    - Go to https://huggingface.co/settings/tokens
 *    - Create a new token with Read access
 *    - Copy the token
 * 
 * 2. Run the migration:
 *    - Open http://localhost/Hackathon/migrate.php
 *    - This adds the required database columns
 * 
 * 3. Set your token:
 *    - Log in to Hackathon
 *    - Click "Settings" (gear icon)
 *    - Paste your token and click "Save Token"
 *    - (Optional) Change the model if desired
 * 
 * 4. Start using!
 *    - Go back to chat and start typing
 *    - The app will use your Hugging Face token
 * 
 * Done! 🎉
 */

// Redirect to home page
header('Location: index.php');
exit;
?>
