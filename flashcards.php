<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['summary'])) {
    header('Content-Type: application/json');
    $summary = trim($_POST['summary']);
    $apiKey = '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';// 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 alternative key if not working
    $endpoint = 'https://openrouter.ai/api/v1/chat/completions';
    $payload = [
        'model' => 'openai/gpt-4o',
        'messages' => [
            [
                'role' => 'system',
                'content' => 'You are a helpful assistant for students. Only generate flashcards for school, academic, or scholarly topics (such as math, science, history, language arts, and other subjects taught in schooland stuff related about Artificial Intelligence like its importance or use in our world how it works and informational stuff about AI). If the user asks for flashcards on a non-academic topic (like TV shows, celebrities, pop culture, etc.), respond with a plain English message (not JSON) that says: "Sorry, I can only generate flashcards for academic topics." If the topic is academic, output ONLY valid JSON, and nothing else, in the following format: [{"question":"...","answer":"..."}, ...]. Surround your JSON with <json>...</json> tags.'
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
        echo json_encode(['error' => $refusal ?: 'Failed to parse flashcards. Try again or change api key (emergency api key in comment on the code next to the current one)']);
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
        body {
            background: linear-gradient(135deg, #13547a 0%, #80d0c7 100%);
            min-height: 100vh;
            font-family: 'Montserrat', sans-serif;
        }

        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-weight: 700;
            color: #13547a;
            font-size: 1.5rem;
        }

        .navbar-brand i {
            margin-right: 8px;
        }

        main {
            padding-top: 76px;
            min-height: calc(100vh - 100px);
            background: transparent;
        }

        .hero-section {
            background: transparent;
            padding: 60px 0;
        }

        .section-padding {
            padding: 80px 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }

        .btn {
            padding: 10px 20px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #13547a;
            border: none;
        }

        .btn-primary:hover {
            background: #0d3d5a;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .btn-outline-primary {
            color: #13547a;
            border: 2px solid #13547a;
        }

        .btn-outline-primary:hover {
            background: #13547a;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .form-control {
            border: 2px solid rgba(19, 84, 122, 0.1);
            border-radius: 8px;
            padding: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #13547a;
            box-shadow: 0 0 0 3px rgba(19, 84, 122, 0.1);
        }

        /* Specific styles for quiz */
        .quiz-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            margin-bottom: 30px;
            padding: 25px;
            backdrop-filter: blur(10px);
        }

        .quiz-option {
            background: rgba(255, 255, 255, 0.8);
            border: 2px solid rgba(19, 84, 122, 0.1);
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .quiz-option:hover {
            background: rgba(19, 84, 122, 0.05);
            transform: translateX(5px);
        }

        /* Specific styles for flashcards */
        .flashcard {
            perspective: 1000px;
            margin-bottom: 30px;
            height: 300px;
        }

        .flashcard-inner {
            position: relative;
            width: 100%;
            height: 100%;
            text-align: center;
            transition: transform 0.8s;
            transform-style: preserve-3d;
        }

        .flashcard.flipped .flashcard-inner {
            transform: rotateY(180deg);
        }

        .flashcard-front, .flashcard-back {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px;
            border-radius: 15px;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }

        .flashcard-back {
            transform: rotateY(180deg);
            background: #13547a;
            color: white;
        }

        .custom-form {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .custom-output {
            background: rgba(255, 255, 255, 0.95);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .navbar {
                padding: 0.5rem 0;
            }
            
            .btn {
                padding: 8px 16px;
                font-size: 0.9rem;
            }
            
            .hero-section {
                padding: 40px 0;
            }
            
            .flashcard {
                height: 250px;
            }
        }
    </style>
</head>
<body id="top">
<main>
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi-back"></i>
                <span>Apollo AI</span>
            </a>
            <div class="ms-auto">
                <a href="main.php" class="btn btn-outline-primary me-2">
                    <i class="bi bi-arrow-left-circle"></i> Back to Dashboard
                </a>
                <a href="logout.php" class="btn btn-primary">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
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