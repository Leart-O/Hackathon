<?php
session_start();
require 'db.php'; // Include database connection

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Initialize $conversation_id
$conversation_id = $_GET['conversation_id'] ?? null;

// Fetch conversations for the sidebar
$user_id = $_SESSION['user_id'];
$conversations = $db->query("SELECT * FROM conversations WHERE user_id = $user_id ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Initialize $messages
$messages = [];

if ($conversation_id) {
    // Fetch messages for the selected conversation
    $messages = $db->query("SELECT role, content FROM messages WHERE conversation_id = $conversation_id ORDER BY created_at")
                   ->fetchAll(PDO::FETCH_ASSOC);
}

// Handle new question submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['question'])) {
    $question = trim($_POST['question']);
    $conversation_id = $_POST['conversation_id'] ?? null;

    // Create a new conversation if none exists
    if (!$conversation_id) {
        $db->query("INSERT INTO conversations (user_id) VALUES ($user_id)");
        $conversation_id = $db->lastInsertId();
    }

    // Save user message
    $stmt = $db->prepare("INSERT INTO messages (conversation_id, role, content) VALUES (?, 'user', ?)");
    $stmt->execute([$conversation_id, $question]);

    // Call AI API
    $messages = $db->query("SELECT role, content FROM messages WHERE conversation_id = $conversation_id ORDER BY created_at")
                   ->fetchAll(PDO::FETCH_ASSOC);
    $payload = [
        'model' => 'gpt-4', // Replace with the correct model ID if needed
        'messages' => $messages
    ];
    $ch = curl_init('https://api.aimlapi.com/v1/chat/completions'); // Updated endpoint
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer fd6b7518f2af43288541cd7ecec46e6f' // Replace with your actual API key
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // Log the response and errors
    file_put_contents('api_debug.log', "HTTP Code: $httpCode\nResponse: $response\nError: $curlError\n", FILE_APPEND);
    file_put_contents('api_debug.log', "Payload: " . json_encode($payload, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);

    $data = json_decode($response, true);
    if (isset($data['error']['message'])) {
        $error_message = $data['error']['message'];
        file_put_contents('api_debug.log', "API Error: $error_message\n", FILE_APPEND);
        die("API Error: $error_message");
    }
    $ai_text = $data['choices'][0]['message']['content'] ?? 'Error: no response';

    // Save AI response
    $stmt = $db->prepare("INSERT INTO messages (conversation_id, role, content) VALUES (?, 'assistant', ?)");
    $stmt->execute([$conversation_id, $ai_text]);

    // Redirect with conversation data
    header("Location: main.php?conversation_id=$conversation_id");
    exit;
}

// Fetch conversation from index.php if passed
if (isset($_POST['conversation_data'])) {
    $conversation_data = json_decode($_POST['conversation_data'], true);
    foreach ($conversation_data as $message) {
        $stmt = $db->prepare("INSERT INTO messages (conversation_id, role, content) VALUES (?, ?, ?)");
        $stmt->execute([$conversation_id, $message['role'], $message['content']]);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AI Chat Main</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <main>
        <section class="hero-section d-flex justify-content-center align-items-center">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12 text-center">
                        <h1 class="text-white">AI Chat</h1>
                    </div>
                </div>
            </div>
        </section>
        <section class="section-padding">
            <div class="container">
                <div class="row">
                    <div class="col-lg-3">
                        <h3>Conversations</h3>
                        <ul class="list-group">
                            <?php foreach ($conversations as $conv): ?>
                                <li class="list-group-item">
                                    <a href="main.php?conversation_id=<?= $conv['id'] ?>"><?= htmlspecialchars($conv['title']) ?></a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <a href="main.php" class="btn btn-primary mt-3">New Conversation</a>
                    </div>
                    <div class="col-lg-9">
                        <h2>Conversation</h2>
                        <?php if ($conversation_id): ?>
                            <?php foreach ($messages as $msg): ?>
                                <div class="alert alert-<?= $msg['role'] === 'user' ? 'primary' : 'secondary' ?>">
                                    <strong><?= ucfirst($msg['role']) ?>:</strong>
                                    <?= nl2br(htmlspecialchars($msg['content'])) ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>Select a conversation or start a new one.</p>
                        <?php endif; ?>
                        <form method="POST">
                            <input type="hidden" name="conversation_id" value="<?= $conversation_id ?>">
                            <div class="form-group">
                                <label for="question">Ask AI:</label>
                                <textarea id="question" name="question" class="form-control" rows="3"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3">Send</button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>
</body>
</html>