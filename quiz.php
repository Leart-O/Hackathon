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
        .quiz-card {
            margin-bottom: 25px;
            border-radius: var(--border-radius-medium);
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            border: none;
            background: var(--section-bg-color);
        }

        .quiz-card .card-body {
            padding: 1.5rem;
        }

        .quiz-option {
            cursor: pointer;
            border-radius: var(--border-radius-small);
            margin-bottom: 10px;
            padding: 10px 16px;
            background: #fff;
            border: 1px solid var(--border-color);
            transition: background 0.2s, color 0.2s, border 0.2s;
            display: flex;
            align-items: center;
        }

        .quiz-option.selected {
            background: var(--secondary-color);
            color: #fff;
            border-color: var(--secondary-color);
        }

        .quiz-option.correct {
            background: #28a745 !important;
            color: #fff;
            border-color: #28a745;
        }

        .quiz-option.incorrect {
            background: #dc3545 !important;
            color: #fff;
            border-color: #dc3545;
        }

        .quiz-option input[type="radio"] {
            margin-right: 12px;
        }

        #quiz-result .alert {
            font-size: 1.1rem;
            margin-top: 20px;
        }

        @media (max-width: 600px) {
            .quiz-card .card-body { padding: 1rem; }
            .quiz-option { font-size: 0.98rem; padding: 8px 8px; }
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
        quiz.forEach((q, idx) => {
            const selected = document.querySelector(`.quiz-option[data-q="${idx}"].selected`);
            document.querySelectorAll(`.quiz-option[data-q="${idx}"]`).forEach(o => {
                o.classList.remove('correct', 'incorrect');
            });
            if (selected) {
                const selectedIdx = parseInt(selected.getAttribute('data-idx'));
                const correctIdx = "ABCD".indexOf(q.answer.trim().toUpperCase());
                if (selectedIdx === correctIdx) {
                    correct++;
                    selected.classList.add('correct');
                } else {
                    selected.classList.add('incorrect');
                    
                    document.querySelectorAll(`.quiz-option[data-q="${idx}"]`).forEach(o => {
                        if (parseInt(o.getAttribute('data-idx')) === correctIdx) {
                            o.classList.add('correct');
                        }
                    });
                }
            }
        });
        document.getElementById('quiz-result').innerHTML = `<div class="alert alert-info">You got ${correct} out of ${total} correct.</div>`;
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
$('#output').html(marked.parse(json.result));
if (window.MathJax) MathJax.typesetPromise([document.getElementById('output')]);
</script>
</body>
</html>