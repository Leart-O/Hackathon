<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Put the API key in one place so both handlers can use it
$apiKey = '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'; // existing key in your file

// Explain endpoint: returns one short sentence explanation for a question's correct answer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'explain') {
    header('Content-Type: application/json');
    $question = trim($_POST['question'] ?? '');
    $correct = trim($_POST['correct'] ?? '');

    if ($question === '' || $correct === '') {
        echo json_encode(['error' => 'Missing question or correct answer.']);
        exit;
    }

    $endpoint = 'https://openrouter.ai/api/v1/chat/completions';
    $payload = [
        'model' => 'openai/gpt-4o',
        'messages' => [
            [
                'role' => 'system',
                'content' => 'You are a helpful teaching assistant. Provide a single concise (1-2 sentence) plain-English explanation why the correct answer is correct for the provided multiple-choice question. Do not output JSON—only the explanation text.'
            ],
            [
                'role' => 'user',
                'content' => "Question: {$question}\nCorrect answer (text): {$correct}\nProvide a short explanation for why that answer is correct."
            ]
        ],
        'max_tokens' => 80,
        'temperature' => 0.2,
    ];

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
        'X-Title: Hackathon Explain'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlError) {
        echo json_encode(['error' => "cURL error: $curlError"]);
        exit;
    }
    if ($httpCode >= 400) {
        echo json_encode(['error' => "API error (HTTP $httpCode). Response: " . ($response ?: 'empty')]);
        exit;
    }

    $data = json_decode($response, true);
    $content = $data['choices'][0]['message']['content'] ?? $response ?? '';
    $explanation = trim(strip_tags($content));
    if ($explanation === '') {
        echo json_encode(['error' => 'No explanation returned by API.']);
    } else {
        echo json_encode(['explanation' => $explanation]);
    }
    exit;
}

// Original quiz generation handler (unchanged except moved $apiKey is used)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['summary'])) {
    header('Content-Type: application/json');
    $summary = trim($_POST['summary']);
    $endpoint = 'https://openrouter.ai/api/v1/chat/completions';
    $payload = [
        'model' => 'openai/gpt-4o',
        'messages' => [
            [
                'role' => 'system',
                'content' => 'You are a helpful assistant for students. Only generate quizzes for school, academic, or scholarly topics (such as math, science, history, language arts, and other subjects taught in schooland stuff related about Artificial Intelligence like its importance or use in our world how it works and informational stuff about AI). If the user asks for a quiz on a non-academic topic (like TV shows, celebrities, pop culture, etc.), respond with a plain English message (not JSON) that says: "Sorry, I can only generate quizzes for academic topics." If the topic is academic, output ONLY valid JSON, and nothing else, in the following format: [{"question":"...","options":["A","B","C","D"],"answer":"A"}, ...]. Surround your JSON with <json>...</json> tags.'
            ],
            [
                'role' => 'user',
                'content' => "Create a short quiz (3-5 questions, multiple choice only) to help me study the following topic:\n\n" . $summary
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
        'X-Title: Hackathon Quiz'
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

    if (preg_match('/<json>(.*?)<\/json>/is', $content, $m)) {
        $json_str = trim($m[1]);
    } else {
        $json_str = $content;
    }
    $quiz = json_decode($json_str, true);
    if (is_array($quiz)) {
        echo json_encode(['quiz' => $quiz]);
    } else {
        $refusal = trim(strip_tags($content));
        echo json_encode(['error' => $refusal ?: 'Failed to parse quiz. Try again or change api key (emergency api key in comment on the code next to the current one)']);
    }
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AI Quiz Generator</title>
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
                    <h1 class="text-white text-center">AI Quiz Generator</h1>
                    <h6 class="text-center">Enter a summary of what you want to study and get a quiz instantly!</h6>
                    <form id="quiz-form" class="custom-form mt-4">
                        <textarea style="ma" name="summary" id="summary" class="form-control mb-3" rows="4" placeholder="Write a short summary of what you need to study..." required></textarea>
                        <button type="submit" class="btn custom-btn w-100 mb-3">Generate Quiz</button>
                        <br>
                    </form>
                    <div id="quiz-area"></div>
                    <div class="custom-error" id="error" style="display:none;"></div>
                    <div class="custom-output" id="output" style="display:none;"></div>
                </div>
            </div>
        </div>
    </section>
</main>
<script>
const quizArea = document.getElementById('quiz-area');
const errorEl = document.getElementById('error');
document.getElementById('quiz-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    quizArea.innerHTML = '';
    errorEl.style.display = 'none';
    const summary = document.getElementById('summary').value.trim();
    quizArea.innerHTML = '<div class="text-center my-4">Loading…</div>';
    try {
        const resp = await fetch('quiz.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ summary })
        });
        const json = await resp.json();
        if (json.error) {
            quizArea.innerHTML = '';
            errorEl.textContent = json.error;
            errorEl.style.display = 'block';
        } else {
            renderQuiz(json.quiz);
        }
    } catch (err) {
        quizArea.innerHTML = '';
        errorEl.textContent = 'Fetch error: ' + err.message;
        errorEl.style.display = 'block';
    }
});

function renderQuiz(quiz) {
    let html = '';
    quiz.forEach((q, idx) => {
        html += `<div class="card quiz-card">
            <div class="card-body">
                <h5 class="card-title">Q${idx+1}: ${q.question}</h5>
                <div>`;
        q.options.forEach((opt, oidx) => {
            html += `<div class="form-check quiz-option" data-q="${idx}" data-idx="${oidx}">
                <input class="form-check-input" type="radio" name="q${idx}" id="q${idx}o${oidx}">
                <label class="form-check-label" for="q${idx}o${oidx}">${opt}</label>
            </div>`;
        });
        html += `</div></div></div>`;
    });
    html += `<button class="btn custom-btn w-100 mt-3" id="submit-quiz">Submit Quiz</button>
    <div id="quiz-result" class="mt-4"></div>`;
    quizArea.innerHTML = html;

   
    document.querySelectorAll('.quiz-option').forEach(opt => {
        opt.addEventListener('click', function() {
            const q = this.getAttribute('data-q');
            document.querySelectorAll(`.quiz-option[data-q="${q}"]`).forEach(o => o.classList.remove('selected'));
            this.classList.add('selected');
            this.querySelector('input').checked = true;
        });
    });

    document.getElementById('submit-quiz').onclick = function() {
        let correct = 0, total = quiz.length;
        const perQ = [];

        quiz.forEach((q, idx) => {
            const selected = document.querySelector(`.quiz-option[data-q="${idx}"].selected`);
            // clear classes
            document.querySelectorAll(`.quiz-option[data-q="${idx}"]`).forEach(o => {
                o.classList.remove('correct', 'incorrect');
            });

            const correctIdx = "ABCD".indexOf((q.answer || '').trim().toUpperCase());
            const correctLabel = "ABCD"[correctIdx] || '';
            const correctText = q.options && q.options[correctIdx] ? q.options[correctIdx] : '';

            if (selected) {
                const selectedIdx = parseInt(selected.getAttribute('data-idx'));
                if (selectedIdx === correctIdx) {
                    correct++;
                    selected.classList.add('correct');
                    perQ.push({ idx, ok: true, correctLabel, correctText, selectedLabel: "ABCD"[selectedIdx] });
                } else {
                    selected.classList.add('incorrect');
                    // highlight the correct option
                    document.querySelectorAll(`.quiz-option[data-q="${idx}"]`).forEach(o => {
                        if (parseInt(o.getAttribute('data-idx')) === correctIdx) o.classList.add('correct');
                    });
                    perQ.push({ idx, ok: false, correctLabel, correctText, selectedLabel: "ABCD"[selectedIdx] });
                }
            } else {
                // no answer selected
                document.querySelectorAll(`.quiz-option[data-q="${idx}"]`).forEach(o => {
                    if (parseInt(o.getAttribute('data-idx')) === correctIdx) o.classList.add('correct');
                });
                perQ.push({ idx, ok: false, correctLabel, correctText, selectedLabel: null });
            }
        });

        // Build result display with per-question feedback and explanation buttons
        let resultHtml = `<div class="alert alert-info">You got ${correct} out of ${total} correct.</div>`;
        resultHtml += `<div class="list-group">`;
        perQ.forEach(item => {
            const q = quiz[item.idx];
            const statusClass = item.ok ? 'text-success' : 'text-danger';
            const statusText = item.ok ? 'Correct' : 'Incorrect';
            const yourAnswer = item.selectedLabel ? `Your answer: <strong>${item.selectedLabel}</strong>` : `<em>No answer selected</em>`;

            // Explanation area: use provided explanation if present, otherwise add a "Show explanation" button
            let explanationHtml = '';
            if (!item.ok) {
                if (q.explanation && q.explanation.trim() !== '') {
                    explanationHtml = `<div class="small text-muted mt-2">${q.explanation}</div>`;
                } else {
                    explanationHtml = `<div class="mt-2"><button class="btn btn-sm btn-link explain-btn" data-q="${item.idx}">Show explanation</button></div><div id="explain-${item.idx}" class="mt-2"></div>`;
                }
            }

            resultHtml += `<div class="list-group-item">
                <div><strong>Q${item.idx + 1}:</strong> ${q.question}</div>
                <div class="${statusClass}" style="margin-top:6px;"><strong>${statusText}</strong></div>
                <div style="margin-top:6px;">${yourAnswer}</div>
                <div style="margin-top:6px;"><strong>Correct:</strong> ${item.correctLabel} &mdash; ${item.correctText}</div>
                ${explanationHtml}
            </div>`;
        });
        resultHtml += `</div>`;

        document.getElementById('quiz-result').innerHTML = resultHtml;
        // smooth scroll to results
        document.getElementById('quiz-result').scrollIntoView({ behavior: 'smooth' });

        // attach handlers for explanation buttons (use closure 'quiz' array)
        document.querySelectorAll('.explain-btn').forEach(btn => {
            btn.addEventListener('click', async function() {
                const idx = this.getAttribute('data-q');
                const el = document.getElementById('explain-' + idx);
                if (!el) return;
                if (el.dataset.loading === '1') return;
                el.dataset.loading = '1';
                el.innerHTML = '<div class="small text-muted">Loading explanation…</div>';
                try {
                    const questionText = (quiz[idx].question || '').toString().trim();
                    const answerRaw = (quiz[idx].answer || '').toString().trim();
                    // try to map A/B/C/D to option text; fallback to answerRaw
                    let correctText = '';
                    const correctIdx = "ABCD".indexOf(answerRaw.toUpperCase());
                    if (correctIdx >= 0 && Array.isArray(quiz[idx].options) && quiz[idx].options[correctIdx]) {
                        correctText = quiz[idx].options[correctIdx];
                    } else if (answerRaw.length > 0) {
                        correctText = answerRaw; // fallback to whatever the answer field contains
                    }
                    if (!questionText || !correctText) {
                        el.innerHTML = `<div class="small text-danger">Cannot fetch explanation: missing question or correct answer text.</div>`;
                        el.dataset.loading = '0';
                        return;
                    }
                    const resp = await fetch('quiz.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({ action: 'explain', question: questionText, correct: correctText })
                    });
                    const json = await resp.json();
                    if (json.explanation) {
                        el.innerHTML = `<div class="small text-muted">${json.explanation}</div>`;
                    } else {
                        el.innerHTML = `<div class="small text-danger">${json.error || 'No explanation available.'}</div>`;
                        el.dataset.loading = '0';
                    }
                } catch (err) {
                    el.innerHTML = `<div class="small text-danger">Fetch error: ${err.message}</div>`;
                    el.dataset.loading = '0';
                }
            });
        });
    };
}
</script>
<script src="js/main.js"></script>
<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
<script id="MathJax-script" async
  src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
<script>
/* kept for compatibility with other code portions that may set #output */
try {
  if (typeof json !== 'undefined' && json && json.result) {
    $('#output').html(marked.parse(json.result));
    if (window.MathJax) MathJax.typesetPromise([document.getElementById('output')]);
  }
} catch(e){}
</script>
</body>
</html>