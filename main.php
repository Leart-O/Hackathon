<?php
session_start();
require 'db.php';

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

    // Fetch all messages for this conversation
    $messages = $db->query("SELECT role, content FROM messages WHERE conversation_id = $conversation_id ORDER BY created_at")
                   ->fetchAll(PDO::FETCH_ASSOC);

    // Call OpenRouter
    $apiKey = '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'; // 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 alternative key if not working
    $endpoint = 'https://openrouter.ai/api/v1/chat/completions';

    $payload = [
        'model' => 'openai/gpt-4o',
        'messages' => [],
        'max_tokens' => 300,
        'temperature' => 0.7,
    ];

    // Add a strict system prompt for academic topics only
    array_unshift($payload['messages'], [
        'role' => 'system',
        'content' => 'You are a helpful assistant for students. Only answer questions related to school, academic, or scholarly topics (such as math, science, history, language arts, and other subjects taught in school and stuff related about Artificial Intelligence like its importance or use in our world how it works and informational stuff about AI). If a user asks about anything not related to school or learning, respond ONLY with: "Sorry, I can only answer questions about academic topics." Do not provide any other information.'
    ]);

    foreach ($messages as $msg) {
        $payload['messages'][] = [
            'role' => $msg['role'],
            'content' => $msg['content']
        ];
    }

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
        'HTTP-Referer: https://yourdomain.com',
        'X-Title: Hackathon Chat'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    
    file_put_contents('api_debug.log', "HTTP Code: $httpCode\nResponse: $response\nError: $curlError\n", FILE_APPEND);
    file_put_contents('api_debug.log', "Payload: " . json_encode($payload, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);

    $data = json_decode($response, true);
    if (isset($data['choices'][0]['message']['content'])) {
        $ai_text = $data['choices'][0]['message']['content'];
    } else {
        $ai_text = 'Error: no response';
    }

    
    $stmt = $db->prepare("INSERT INTO messages (conversation_id, role, content) VALUES (?, 'assistant', ?)");
    $stmt->execute([$conversation_id, $ai_text]);

    
    header("Location: main.php?conversation_id=$conversation_id");
    exit;
}


if (isset($_POST['conversation_data'])) {
    $conversation_data = json_decode($_POST['conversation_data'], true);
    foreach ($conversation_data as $message) {
        $stmt = $db->prepare("INSERT INTO messages (conversation_id, role, content) VALUES (?, ?, ?)");
        $stmt->execute([$conversation_id, $message['role'], $message['content']]);
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rename_conversation_id'], $_POST['new_title'])) {
    $cid = (int)$_POST['rename_conversation_id'];
    $new_title = trim($_POST['new_title']);
    if ($new_title !== '') {
        $stmt = $db->prepare("UPDATE conversations SET title = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$new_title, $cid, $_SESSION['user_id']]);
    }
    header("Location: main.php?conversation_id=$cid");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_conversation_id'])) {
    $cid = (int)$_POST['delete_conversation_id'];
    $stmt = $db->prepare("DELETE FROM conversations WHERE id = ? AND user_id = ?");
    $stmt->execute([$cid, $_SESSION['user_id']]);
    header("Location: main.php");
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Apollo AI</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700&family=Open+Sans&display=swap" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        .chat-message {
            margin-bottom: 15px;
            border-radius: 10px;
            padding: 15px;
        }
        .chat-message.user {
            background: #e3f2fd;
            text-align: right;
        }
        .chat-message.assistant {
            background: #f8f9fa;
            text-align: left;
        }
        .sidebar {
            background: var(--section-bg-color);
            border-radius: var(--border-radius-medium);
            padding: 20px;
            min-height: 400px;
        }
        .sidebar .list-group-item.active {
            background: var(--primary-color);
            color: #fff;
            border-color: var(--primary-color);
        }
    </style>
    <script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
    <script id="MathJax-script" async
  src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
</head>
<body id="top">
<main>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi-back"></i>
                <span>Apollo AI</span>
            </a>
            <div class="d-lg-none ms-auto me-4">
                <a href="#top" class="navbar-icon bi-person smoothscroll"></a>
            </div>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-lg-5 me-lg-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php#section_1">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php#section_2">Browse Topics</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php#section_3">How it works</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php#section_4">FAQs</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php#section_5">Contact</a></li>
                </ul>
                <div class="d-none d-lg-block">
                    <a href="#top" class="navbar-icon bi-person smoothscroll"></a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="logout.php" class="btn btn-outline-danger ms-2">Logout</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <section class="hero-section d-flex justify-content-center align-items-center" style="padding-bottom:40px;">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center">
                    <h1 class="text-white">AI Chat</h1>
                    <h6 class="text-white">Continue your conversation or start a new one</h6>
                </div>
            </div>
        </div>
    </section>

    <section class="section-padding">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 sidebar">
                    <h3>Conversations</h3>
                    <ul class="list-group">
                        <?php foreach ($conversations as $conv): ?>
                            <li class="list-group-item<?= ($conv['id'] == $conversation_id) ? ' active' : '' ?>">
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="main.php?conversation_id=<?= $conv['id'] ?>" style="text-decoration:none;<?= ($conv['id'] == $conversation_id) ? 'color:#fff;' : '' ?>">
                                        <?= htmlspecialchars($conv['title']) ?>
                                    </a>
                                    <div>
                                        
                                        <button class="btn btn-sm btn-outline-primary py-0 px-2 ms-1" onclick="showRenameForm(<?= $conv['id'] ?>, '<?= htmlspecialchars(addslashes($conv['title'])) ?>')">✏️</button>
                                       
                                        <form method="POST" action="main.php" style="display:inline;" onsubmit="return confirm('Delete this conversation?');">
                                            <input type="hidden" name="delete_conversation_id" value="<?= $conv['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2 ms-1">🗑️</button>
                                        </form>
                                    </div>
                                </div>
                                
                                <form method="POST" action="main.php" id="rename-form-<?= $conv['id'] ?>" style="display:none; margin-top:5px;">
                                    <input type="hidden" name="rename_conversation_id" value="<?= $conv['id'] ?>">
                                    <input type="text" name="new_title" class="form-control form-control-sm" style="width:70%; display:inline;" required>
                                    <button type="submit" class="btn btn-sm btn-success py-0 px-2 ms-1">Save</button>
                                    <button type="button" class="btn btn-sm btn-secondary py-0 px-2 ms-1" onclick="hideRenameForm(<?= $conv['id'] ?>)">Cancel</button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                        <li class="list-group-item">
                            <a href="quiz.php" style="text-decoration:none;">📝 Quiz Generator</a>
                        </li>
                        <li class="list-group-item">
                            <a href="flashcards.php" style="text-decoration:none;">📇 Flashcards</a>
                        </li>
                    </ul>
                    <a href="main.php" class="btn btn-primary mt-3 w-100">New Conversation</a>
                </div>
                <div class="col-lg-9">
                    <h2>Conversation</h2>
                    <div style="min-height:300px;">
                        <?php if ($conversation_id): ?>
                            <?php foreach ($messages as $msg): ?>
                                <div class="chat-message <?= $msg['role'] ?>">
                                    <strong><?= ucfirst($msg['role']) ?>:</strong>
                                    <?= nl2br(htmlspecialchars($msg['content'])) ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>Select a conversation or start a new one.</p>
                        <?php endif; ?>
                    </div>
                    <form method="POST" class="custom-form mt-4">
                        <input type="hidden" name="conversation_id" value="<?= $conversation_id ?>">
                        <div class="form-group mb-3">
                            <label for="question">Ask AI:</label>
                            <textarea id="question" name="question" class="form-control" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="btn custom-btn">Send</button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</main>
<footer class="site-footer section-padding">
    <div class="container">
        <div class="row">
            <div class="col-lg-3 col-12 mb-4 pb-2">
                <a class="navbar-brand mb-2" href="index.php">
                    <i class="bi-back"></i>
                    <span>Apollo AI</span>
                </a>
            </div>
            <div class="col-lg-3 col-md-4 col-6"></div>
            <div class="col-lg-3 col-md-4 col-6 mb-4 mb-lg-0"></div>
        </div>
    </div>
</footer>
<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>
<script src="js/jquery.sticky.js"></script>
<script src="js/custom.js"></script>
<script>
function showRenameForm(id, title) {
    document.getElementById('rename-form-' + id).style.display = 'block';
    document.getElementById('rename-form-' + id).querySelector('input[name="new_title"]').value = title;
}
function hideRenameForm(id) {
    document.getElementById('rename-form-' + id).style.display = 'none';
}
</script>
</body>
</html>