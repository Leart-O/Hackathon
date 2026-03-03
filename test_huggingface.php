<?php
/**
 * Quick test script to verify Hugging Face API integration
 * 
 * Run this to test your setup:
 * php test_huggingface.php
 */

require 'db.php';
require 'huggingface_api.php';

echo "=== Hugging Face API Integration Test ===\n\n";

// Get the first user for testing
$user_id = null;
$stmt = $db->query("SELECT id FROM users LIMIT 1");
$first_user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($first_user) {
    $user_id = $first_user['id'];
    echo "Found user with ID: $user_id\n\n";
} else {
    echo "✗ No users found in database\n";
    echo "Please create an account first\n";
    exit(1);
}

echo "1. Checking database connection... ";
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Connected (Found {$result['count']} users)\n\n";
} catch (Exception $e) {
    echo "✗ Failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Check user's token
echo "2. Checking user's Hugging Face token... \n";
try {
    $stmt = $db->prepare("SELECT huggingface_token, huggingface_model FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "   Raw data from DB: ";
    var_dump($user);
    echo "\n";
    
    if ($user && isset($user['huggingface_token']) && !empty($user['huggingface_token'])) {
        $token_preview = substr($user['huggingface_token'], 0, 10) . "...";
        echo "   ✓ Token configured ({$token_preview})\n";
        echo "   Token length: " . strlen($user['huggingface_token']) . " characters\n";
        echo "   Model: " . ($user['huggingface_model'] ?: 'default (gpt2)') . "\n\n";
    } else {
        echo "   ✗ No token configured\n";
        if ($user === false) echo "   (User record not found)\n";
        else if (!isset($user['huggingface_token'])) echo "   (Token key doesn't exist in result)\n";
        else echo "   (Token is empty or null)\n";
        echo "   Please visit /settings.php to add your Hugging Face token\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test API call
echo "3. Testing API call to Hugging Face... \n";
try {
    $messages = [
        ['role' => 'user', 'content' => 'Say "Hello, World!"']
    ];
    
    $response = callHuggingFaceAPI($messages, $user_id, $db, 50);
    
    if (strlen($response) > 0) {
        echo "✓ API call successful!\n";
        echo "   Response: " . substr($response, 0, 100) . "...\n\n";
    } else {
        echo "✗ Empty response from API\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "✗ API Error: " . $e->getMessage() . "\n\n";
    echo "4. Checking api_debug.log for details...\n";
    
    if (file_exists('api_debug.log')) {
        $log = file_get_contents('api_debug.log');
        $lines = array_slice(explode("\n", $log), -20);
        foreach ($lines as $line) {
            if (!empty($line)) {
                echo "   " . $line . "\n";
            }
        }
    }
    exit(1);
}

// Success!
echo "4. All tests passed! ✓\n";
echo "\nYour Hugging Face API is configured correctly.\n";
echo "You can now use Chat, Flashcards, and Quiz features.\n";
?>
