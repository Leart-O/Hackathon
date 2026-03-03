<?php
// Test your Hugging Face token
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    die('Please log in first');
}

$user_id = $_SESSION['user_id'];
$stmt = $db->prepare("SELECT huggingface_token FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$token = $user['huggingface_token'] ?? null;

if (!$token) {
    die('No token found. Please go to Settings and add your Hugging Face token.');
}

echo "Testing token: " . substr($token, 0, 10) . "...<br><br>";

// Test correct inference API endpoints
$tests = [
    [
        'name' => 'Hugging Face Inference API (text-generation)',
        'url' => 'https://api-inference.huggingface.co/models/gpt2',
        'payload' => ['inputs' => 'Hello']
    ],
    [
        'name' => 'Router with /chat/completions',
        'url' => 'https://router.huggingface.co/chat/completions',
        'payload' => ['model' => 'gpt2', 'messages' => [['role' => 'user', 'content' => 'hello']], 'max_tokens' => 10]
    ],
    [
        'name' => 'Router direct model inference',
        'url' => 'https://router.huggingface.co/models/gpt2',
        'payload' => ['inputs' => 'Hello']
    ]
];

foreach ($tests as $test) {
    echo "<h4>" . $test['name'] . "</h4>";
    echo "URL: " . $test['url'] . "<br>";
    
    $ch = curl_init($test['url']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test['payload']));
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    echo "HTTP Code: <strong>$httpCode</strong><br>";
    if ($curlError) echo "cURL Error: $curlError<br>";
    echo "Raw Response: " . substr($response, 0, 500) . "<br>";
    echo "Parsed: <pre>" . json_encode(json_decode($response, true), JSON_PRETTY_PRINT) . "</pre>";
    echo "<hr>";
}
?>
