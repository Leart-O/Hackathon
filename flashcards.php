<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['summary'])) {
    header('Content-Type: application/json');
    $summary = trim($_POST['summary']);
    $apiKey = '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';
    $endpoint = 'https://openrouter.ai/api/v1/chat/completions';
    $payload = [
        'model' => 'openai/gpt-4o',
        'messages' => [
            [
                'role' => 'system',
                'content' => 'You are a helpful assistant for students. Only generate flashcards for school, academic, or scholarly topics (such as math, science, history, language arts, and other subjects taught in school). If the user asks for flashcards on a non-academic topic (like TV shows, celebrities, pop culture, etc.), respond with a plain English message (not JSON) that says: "Sorry, I can only generate flashcards for academic topics." If the topic is academic, output ONLY valid JSON, and nothing else, in the following format: [{"question":"...","answer":"..."}, ...]. Surround your JSON with <json>...</json> tags.'
            ],
            [
                'role' => 'user',
                'content' => "Create a set of flashcards (question/term and answer/explanation) to help me study the following topic:\n\n" . $summary
            ]
        ],
        'max_tokens' => 700,
        'temperature' => 0.7,
    ];
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
        'HTTP-Referer: https://yourdomain.com',
        'X-Title: Hackathon Flashcards'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);
    if ($curlError) {
        echo json_encode(['error' => "cURL error: $curlError"]);
        exit;
    }
    $data = json_decode($response, true);
    $content = $data['choices'][0]['message']['content'] ?? '';

    // Extract JSON between <json>...</json>
    if (preg_match('/<json>(.*?)<\/json>/is', $content, $m)) {
        $json_str = trim($m[1]);
    } else {
        $json_str = $content;
    }
    $cards = json_decode($json_str, true);
    if (is_array($cards)) {
        echo json_encode(['cards' => $cards]);
    } else {
        // If not valid JSON, show the AI's message as an error
        $refusal = trim(strip_tags($content));
        echo json_encode(['error' => $refusal ?: 'Failed to parse flashcards. Try again.']);
    }
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AI Flashcards Generator</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        /* Flashcard container improvements */
        .flashcard-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 30px;
            width: 100%;
        }

        /* Responsive flashcard size */
        .flashcard {
            width: 100%;
            max-width: 400px;
            min-height: 180px;
            height: auto;
            perspective: 1000px;
            margin-bottom: 20px;
        }

        .flashcard-inner {
            position: relative;
            width: 100%;
            min-height: 180px;
            transition: transform 0.6s;
            transform-style: preserve-3d;
        }

        .flashcard.flipped .flashcard-inner { transform: rotateY(180deg); }

        .flashcard-front, .flashcard-back {
            position: absolute;
            width: 100%;
            min-height: 180px;
            box-sizing: border-box;
            backface-visibility: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--border-radius-medium);
            background: var(--section-bg-color);
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            font-size: 1.15rem;
            padding: 24px;
            text-align: center;
            overflow-y: auto;
            word-break: break-word;
            max-height: 300px;
        }

        .flashcard-back { transform: rotateY(180deg); }

        .flashcard-controls button { margin: 0 10px; }

        @media (max-width: 600px) {
            .flashcard { max-width: 98vw; }
            .flashcard-front, .flashcard-back { font-size: 1rem; padding: 12px; }
        }
    </style>
</head>
<body id="top">
<main>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi-back"></i>
                <span>Apollo AI</span>
            </a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="logout.php" class="btn btn-outline-danger ms-2">Logout</a>
            <?php endif; ?>
        </div>
    </nav>
    <section class="hero-section d-flex justify-content-center align-items-center" style="padding-bottom:40px;">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 col-12 mx-auto">
                    <h1 class="text-white text-center">AI Flashcards Generator</h1>
                    <h6 class="text-center">Enter a summary of what you want to study and get interactive flashcards instantly!</h6>
                    <form id="flashcard-form" class="custom-form mt-4">
                        <textarea name="summary" id="summary" class="form-control mb-3" rows="4" placeholder="Write a short summary of what you need to study..." required></textarea>
                        <button type="submit" class="btn custom-btn w-100">Generate Flashcards</button>
                    </form>
                    <div id="flashcard-area"></div>
                    <div class="custom-error" id="error" style="display:none;"></div>
                    <div class="custom-output" id="output" style="display:none;"></div>
                </div>
            </div>
        </div>
    </section>
    
</main>
<script>
const flashcardArea = document.getElementById('flashcard-area');
const errorEl = document.getElementById('error');
let flashcards = [], current = 0, flipped = false;

document.getElementById('flashcard-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    flashcardArea.innerHTML = '';
    errorEl.style.display = 'none';
    const summary = document.getElementById('summary').value.trim();
    flashcardArea.innerHTML = '<div class="text-center my-4">Loading…</div>';
    try {
        const resp = await fetch('flashcards.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ summary })
        });
        const json = await resp.json();
        if (json.error) {
            flashcardArea.innerHTML = '';
            errorEl.textContent = json.error;
            errorEl.style.display = 'block';
        } else {
            flashcards = json.cards;
            current = 0;
            flipped = false;
            renderFlashcard();
        }
    } catch (err) {
        flashcardArea.innerHTML = '';
        errorEl.textContent = 'Fetch error: ' + err.message;
        errorEl.style.display = 'block';
    }
});

function renderFlashcard() {
    if (!flashcards.length) return;
    const card = flashcards[current];
    flashcardArea.innerHTML = `
        <div class="flashcard-container">
            <div class="flashcard${flipped ? ' flipped' : ''}">
                <div class="flashcard-inner">
                    <div class="flashcard-front">${card.question}</div>
                    <div class="flashcard-back">${card.answer}</div>
                </div>
            </div>
            <div class="flashcard-controls">
                <button class="btn custom-btn" id="flip-btn">${flipped ? 'Show Question' : 'Show Answer'}</button>
                <button class="btn btn-outline-secondary" id="prev-btn" ${current === 0 ? 'disabled' : ''}><i class="bi bi-arrow-left"></i> Prev</button>
                <button class="btn btn-outline-secondary" id="next-btn" ${current === flashcards.length-1 ? 'disabled' : ''}>Next <i class="bi bi-arrow-right"></i></button>
            </div>
            <div class="mt-2 text-center text-muted">Card ${current+1} of ${flashcards.length}</div>
        </div>
    `;
    document.getElementById('flip-btn').onclick = () => { flipped = !flipped; renderFlashcard(); };
    document.getElementById('prev-btn').onclick = () => { if (current > 0) { current--; flipped = false; renderFlashcard(); } };
    document.getElementById('next-btn').onclick = () => { if (current < flashcards.length-1) { current++; flipped = false; renderFlashcard(); } };
}
</script>
<script src="js/main.js"></script>
<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
<script id="MathJax-script" async
  src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
</body>
</html>