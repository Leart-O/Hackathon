<?php
session_start(); 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['prompt'])) {
    header('Content-Type: application/json');

    $prompt = trim($_POST['prompt']);
    $apiKey = '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'; // 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 alternative key if not working
    $endpoint = 'https://openrouter.ai/api/v1/chat/completions';

    $payload = [
        'model' => 'openai/gpt-4o',
        'messages' => [
            [
                'role' => 'system',
                'content' => 'You are a helpful assistant for students. Only give answers that are below 300 character limit so that a short and helpful description is given. Only answer questions related to school, academic, or scholarly topics (such as Ai(artificial intelligence), math, science, history, language arts, and other subjects taught in school). If a user asks about anything not related to school or learning, respond ONLY with: "Sorry, I can only answer questions about academic topics." Do not provide any other information.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ],
        ],
        'max_tokens'  => 300,
        'temperature' => 0.7,
    ];

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey,
        'HTTP-Referer: https://yourdomain.com',
        'X-Title: Hackathon Index'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        echo json_encode(['error' => "cURL error: $curlError"]);
        exit;
    }

    $data = json_decode($response, true);

    if (isset($data['choices'][0]['message']['content'])) {
        $ai_response = $data['choices'][0]['message']['content'];
        $char_limit = 300;
        if (strlen($ai_response) > $char_limit) {
            $preview = substr($ai_response, 0, $char_limit);
            echo json_encode([
                'result' => nl2br(html_entity_decode($preview)),
                'conversation_data' => json_encode($payload['messages'])
            ]);
        } else {
            echo json_encode([
                'result' => html_entity_decode($ai_response),
                'conversation_data' => json_encode($payload['messages'])
            ]);
        }
    } else {
        echo json_encode([
            'error'        => "Unexpected response structure (HTTP $httpCode)",
            'raw_response' => $response
        ]);
    }
    exit;
}
?>

<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <meta name="description" content="">
        <meta name="author" content="">

        <title>Apollo AI</title>

              
        <link rel="preconnect" href="https://fonts.googleapis.com">
        
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700&family=Open+Sans&display=swap" rel="stylesheet">
                        
        <link href="css/bootstrap.min.css" rel="stylesheet">

        <link href="css/bootstrap-icons.css" rel="stylesheet">

        <link href="css/style.css" rel="stylesheet">  
        
        <style>
    .custom-output {
      padding: 20px;
      background-color: var(--section-bg-color);
      border: 1px solid var(--border-color);
      border-radius: var(--border-radius-medium);
      font-size: var(--p-font-size);
      color: var(--dark-color);
      margin-top: 20px;
      white-space: pre-wrap;
    }
    .custom-error {
      color: red;
      font-weight: bold;
      margin-top: 20px;
    }
    .custom-debug {
      margin-top: 10px;
      font-family: monospace;
      font-size: 0.9em;
      background: #f8f8f8;
      padding: 10px;
      border-radius: var(--border-radius-small);
      overflow-x: auto;
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
                    
                    <div class="d-lg-none ms-auto me-4">
                        <a href="#top" class="navbar-icon bi-person smoothscroll"></a>
                    </div>
    
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
    
                    <div class="collapse navbar-collapse" id="navbarNav">
                        <ul class="navbar-nav ms-lg-5 me-lg-auto">
                            <li class="nav-item">
                                <a class="nav-link click-scroll" href="#section_1">Home</a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link click-scroll" href="#section_2">Browse Topics</a>
                            </li>
    
                            <li class="nav-item">
                                <a class="nav-link click-scroll" href="#section_3">How it works</a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link click-scroll" href="#section_4">FAQs</a>
                            </li>
    
                            <li class="nav-item">
                                <a class="nav-link click-scroll" href="#section_5">Contact</a>
                            </li>

                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarLightDropdownMenuLink" role="button" data-bs-toggle="dropdown" aria-expanded="false">Account</a>

                                <ul class="dropdown-menu dropdown-menu-light" aria-labelledby="navbarLightDropdownMenuLink">
                                    <li><a class="dropdown-item" href="signup.php">Sign Up</a></li>

                                    <li><a class="dropdown-item" href="login.php">Log In</a></li>
                                </ul>
                            </li>
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
            

            <section class="hero-section d-flex justify-content-center align-items-center" id="section_1">
  <div class="container">
    <form id="prompt-form"> 
    <h1 style="color:white">Start your learning journey:</h1>
      <div class="custom-form d-flex">
       
        <input
          type="text"
          name="prompt"
          id="prompt-input"
          class="form-control"
          placeholder="Enter your question..."
          required>
        <button type="submit" class="custom-btn ms-2">Send</button>
      </div>
    </form>
    <div class="row align-items-start mt-4">
      <!-- Left: AI output -->
      <div class="col-lg-8 col-12">
        <div class="custom-output" id="output" style="display:none;"></div>
        <div style="margin-top: 10px;">
          <a href="main.php" class="btn custom-btn">Read more</a>
        </div>
        <div id="error" class="custom-error" style="display:none;"></div>
        <pre id="debug" class="custom-debug" style="display:none;"></pre>
      </div>
      <!-- Right: Image -->
      <div class="col-lg-4 col-12 d-flex align-items-start">
        <div id="topic-image" style="display:none; width:100%;">
          <img id="topic-img-tag" src="" alt="Topic Image" class="img-fluid rounded shadow" style="max-height:300px; width:100%; object-fit:contain;">
        </div>
      </div>
    </div>
  </div>
</section>


            <section class="featured-section">
                <div class="container">
                    <div class="row justify-content-center">

                        <div class="col-lg-4 col-12 mb-4 mb-lg-0">
                            <div class="custom-block bg-white shadow-lg">
                                <a href="#">
                                    <div class="d-flex">
                                        <div>
                                            <h5 class="mb-2">Web Design</h5>

                                            
                                        </div>

                                        <span class="badge bg-design rounded-pill ms-auto">14</span>
                                    </div>

                                    <img src="images/topics/undraw_Remote_design_team_re_urdx.png" class="custom-block-image img-fluid" alt="">
                                </a>
                            </div>
                        </div>

                        <div class="col-lg-6 col-12">
                            <div class="custom-block custom-block-overlay">
                                <div class="d-flex flex-column h-100">
                                    <img src="images/businesswoman-using-tablet-analysis.jpg" class="custom-block-image img-fluid" alt="">

                                    <div class="custom-block-overlay-text d-flex">
                                        <div>
                                            <h5 class="text-white mb-2">Finance</h5>

                                            

                                            <a href="#" class="btn custom-btn mt-2 mt-lg-3">Learn More</a>
                                        </div>

                                        <span class="badge bg-finance rounded-pill ms-auto">25</span>
                                    </div>

                                    <div class="social-share d-flex">
                                        <p class="text-white me-4">Share:</p>

                                        <ul class="social-icon">
                                            <li class="social-icon-item">
                                                <a href="#" class="social-icon-link bi-twitter"></a>
                                            </li>

                                            <li class="social-icon-item">
                                                <a href="#" class="social-icon-link bi-facebook"></a>
                                            </li>

                                            <li class="social-icon-item">
                                                <a href="#" class="social-icon-link bi-pinterest"></a>
                                            </li>
                                        </ul>

                                        <a href="#" class="custom-icon bi-bookmark ms-auto"></a>
                                    </div>

                                    <div class="section-overlay"></div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </section>


            <section class="explore-section section-padding" id="section_2">
                <div class="container">
                    <div class="row">

                        <div class="col-12 text-center">
                            <h2 class="mb-4">Browse Topics</h1>
                        </div>

                    </div>
                </div>

                <div class="container-fluid">
                    <div class="row">
                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="design-tab" data-bs-toggle="tab" data-bs-target="#design-tab-pane" type="button" role="tab" aria-controls="design-tab-pane" aria-selected="true">Biology</button>
                            </li>

                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="marketing-tab" data-bs-toggle="tab" data-bs-target="#marketing-tab-pane" type="button" role="tab" aria-controls="marketing-tab-pane" aria-selected="false">World History</button>
                            </li>

                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="finance-tab" data-bs-toggle="tab" data-bs-target="#finance-tab-pane" type="button" role="tab" aria-controls="finance-tab-pane" aria-selected="false">Mathematics</button>
                            </li>

                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="music-tab" data-bs-toggle="tab" data-bs-target="#music-tab-pane" type="button" role="tab" aria-controls="music-tab-pane" aria-selected="false">Computer Science</button>
                            </li>

                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="education-tab" data-bs-toggle="tab" data-bs-target="#education-tab-pane" type="button" role="tab" aria-controls="education-tab-pane" aria-selected="false">Personal Development</button>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="container">
                    <div class="row">

                        <div class="col-12">
                            <div class="tab-content" id="myTabContent">
                                <div class="tab-pane fade show active" id="design-tab-pane" role="tabpanel" aria-labelledby="design-tab" tabindex="0">
                                    <div class="row">
                                        <div class="col-lg-4 col-md-6 col-12 mb-4 mb-lg-0">
                                            <div class="custom-block bg-white shadow-lg">
                                                <a href="#">
                                                    <div class="d-flex">
                                                        <div>
                                                            <h5 class="mb-2">Cellular Biology</h5>
                                                            <p>Cell structure & function, membranes & transport, cell signaling</p>

                                                           
                                                        </div>

                                                        <span class="badge bg-design rounded-pill ms-auto">14</span>
                                                    </div>

                                                    <img src="images/topics/undraw_Remote_design_team_re_urdx.png" class="custom-block-image img-fluid" alt="">
                                                </a>
                                            </div>
                                        </div>

                                        <div class="col-lg-4 col-md-6 col-12 mb-4 mb-lg-0">
                                            <div class="custom-block bg-white shadow-lg">
                                                <a href="#">
                                                    <div class="d-flex">
                                                        <div>
                                                            <h5 class="mb-2">Genetics</h5>

                                                                <p class="mb-0">Mendelian inheritance, DNA replication & repair, genetic engineering</p>
                                                        </div>

                                                        <span class="badge bg-design rounded-pill ms-auto">75</span>
                                                    </div>

                                                    <img src="images/topics/undraw_Redesign_feedback_re_jvm0.png" class="custom-block-image img-fluid" alt="">
                                                </a>
                                            </div>
                                        </div>

                                        <div class="col-lg-4 col-md-6 col-12">
                                            <div class="custom-block bg-white shadow-lg">
                                                <a href="#">
                                                    <div class="d-flex">
                                                        <div>
                                                            <h5 class="mb-2">Ecology & Evolution</h5>

                                                                <p class="mb-0">Ecosystem dynamics, natural selection, conservation biology</p>
                                                        </div>

                                                        <span class="badge bg-design rounded-pill ms-auto">100</span>
                                                    </div>

                                                    <img src="images/topics/colleagues-working-cozy-office-medium-shot.png" class="custom-block-image img-fluid" alt="">
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="marketing-tab-pane" role="tabpanel" aria-labelledby="marketing-tab" tabindex="0">
                                    <div class="row">
                                        <div class="col-lg-4 col-md-6 col-12 mb-4 mb-lg-3">
                                                <div class="custom-block bg-white shadow-lg">
                                                    <a href="#">
                                                        <div class="d-flex">
                                                            <div>
                                                                <h5 class="mb-2">Ancient Civilizations</h5>

                                                                <p class="mb-0">Mesopotamia & Egypt, Indus Valley & China, Classical Greece & Rome</p>
                                                            </div>

                                                            <span class="badge bg-advertising rounded-pill ms-auto">30</span>
                                                        </div>

                                                        <img src="images/topics/undraw_online_ad_re_ol62.png" class="custom-block-image img-fluid" alt="">
                                                    </a>
                                                </div>
                                            </div>

                                            <div class="col-lg-4 col-md-6 col-12 mb-4 mb-lg-3">
                                                <div class="custom-block bg-white shadow-lg">
                                                    <a href="#">
                                                        <div class="d-flex">
                                                            <div>
                                                                <h5 class="mb-2">Middle Ages to Renaissance</h5>

                                                                <p class="mb-0">Feudalism, the Crusades, Renaissance art & science</p>
                                                            </div>

                                                            <span class="badge bg-advertising rounded-pill ms-auto">65</span>
                                                        </div>

                                                        <img src="images/topics/undraw_Group_video_re_btu7.png" class="custom-block-image img-fluid" alt="">
                                                    </a>
                                                </div>
                                            </div>

                                            <div class="col-lg-4 col-md-6 col-12">
                                                <div class="custom-block bg-white shadow-lg">
                                                    <a href="#">
                                                        <div class="d-flex">
                                                            <div>
                                                                <h5 class="mb-2">Modern Era</h5>

                                                                <p class="mb-0">Industrial Revolution, world wars & geopolitics, globalization</p>
                                                            </div>

                                                            <span class="badge bg-advertising rounded-pill ms-auto">50</span>
                                                        </div>

                                                        <img src="images/topics/undraw_viral_tweet_gndb.png" class="custom-block-image img-fluid" alt="">
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                  </div>

                                <div class="tab-pane fade" id="finance-tab-pane" role="tabpanel" aria-labelledby="finance-tab" tabindex="0">   <div class="row">
                                        <div class="col-lg-6 col-md-6 col-12 mb-4 mb-lg-0">
                                            <div class="custom-block bg-white shadow-lg">
                                                <a href="#">
                                                    <div class="d-flex">
                                                        <div>
                                                            <h5 class="mb-2">Algebra & Number Theory</h5>

                                                            <p class="mb-0">Linear equations, polynomials, prime numbers & divisibility</p>
                                                        </div>

                                                        <span class="badge bg-finance rounded-pill ms-auto">30</span>
                                                    </div>

                                                    <img src="images/topics/undraw_Finance_re_gnv2.png" class="custom-block-image img-fluid" alt="">
                                                </a>
                                            </div>
                                        </div>

                                        <div class="col-lg-6 col-md-6 col-12">
                                            <div class="custom-block custom-block-overlay">
                                                <div class="d-flex flex-column h-100">
                                                    <img src="images/businesswoman-using-tablet-analysis-graph-company-finance-strategy-statistics-success-concept-planning-future-office-room.jpg" class="custom-block-image img-fluid" alt="">

                                                    <div class="custom-block-overlay-text d-flex">
                                                        <div>
                                                            <h5 class="text-white mb-2">Calculus</h5>

                                                            <p class="text-white">Limits & continuity, differentiation applications, basic integration</p>

                                                            <a href="#" class="btn custom-btn mt-2 mt-lg-3">Learn More</a>
                                                        </div>

                                                        <span class="badge bg-finance rounded-pill ms-auto">25</span>
                                                    </div>

                                                    <div class="social-share d-flex">
                                                        <p class="text-white me-4">Share:</p>

                                                        <ul class="social-icon">
                                                            <li class="social-icon-item">
                                                                <a href="#" class="social-icon-link bi-twitter"></a>
                                                            </li>

                                                            <li class="social-icon-item">
                                                                <a href="#" class="social-icon-link bi-facebook"></a>
                                                            </li>

                                                            <li class="social-icon-item">
                                                                <a href="#" class="social-icon-link bi-pinterest"></a>
                                                            </li>
                                                        </ul>

                                                        <a href="#" class="custom-icon bi-bookmark ms-auto"></a>
                                                    </div>

                                                    <div class="section-overlay"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="music-tab-pane" role="tabpanel" aria-labelledby="music-tab" tabindex="0">
                                    <div class="row">
                                        <div class="col-lg-4 col-md-6 col-12 mb-4 mb-lg-3">
                                            <div class="custom-block bg-white shadow-lg">
                                                <a href="#">
                                                    <div class="d-flex">
                                                        <div>
                                                            <h5 class="mb-2">Programming Fundamentals</h5>

                                                            <p class="mb-0">Variables & control flow, data structures (arrays, lists, trees), object-oriented concepts</p>
                                                        </div>

                                                        <span class="badge bg-music rounded-pill ms-auto">45</span>
                                                    </div>

                                                    <img src="images/topics/undraw_Compose_music_re_wpiw.png" class="custom-block-image img-fluid" alt="">
                                                </a>
                                            </div>
                                        </div>

                                        <div class="col-lg-4 col-md-6 col-12 mb-4 mb-lg-3">
                                            <div class="custom-block bg-white shadow-lg">
                                                <a href="#">
                                                    <div class="d-flex">
                                                        <div>
                                                            <h5 class="mb-2">Algorithms & Complexity</h5>

                                                            <p class="mb-0">Sorting & searching, recursion, Big O notation</p>
                                                        </div>

                                                        <span class="badge bg-music rounded-pill ms-auto">45</span>
                                                    </div>

                                                    <img src="images/topics/undraw_happy_music_g6wc.png" class="custom-block-image img-fluid" alt="">
                                                </a>
                                            </div>
                                        </div>

                                        <div class="col-lg-4 col-md-6 col-12">
                                            <div class="custom-block bg-white shadow-lg">
                                                <a href="#">
                                                    <div class="d-flex">
                                                        <div>
                                                            <h5 class="mb-2">Web Development</h5>

                                                            <p class="mb-0">HTML/CSS basics, JavaScript & DOM, RESTful APIs</p>
                                                        </div>

                                                        <span class="badge bg-music rounded-pill ms-auto">20</span>
                                                    </div>

                                                    <img src="images/topics/undraw_Podcast_audience_re_4i5q.png" class="custom-block-image img-fluid" alt="">
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="education-tab-pane" role="tabpanel" aria-labelledby="education-tab" tabindex="0">
                                    <div class="row">
                                        <div class="col-lg-6 col-md-6 col-12 mb-4 mb-lg-3">
                                            <div class="custom-block bg-white shadow-lg">
                                                <a href="#">
                                                    <div class="d-flex">
                                                        <div>
                                                            <h5 class="mb-2">Time Management</h5>

                                                            <p class="mb-0">Prioritization techniques, Pomodoro & productivity hacks, goal setting</p>
                                                        </div>

                                                        <span class="badge bg-education rounded-pill ms-auto">80</span>
                                                    </div>

                                                    <img src="images/topics/undraw_Graduation_re_gthn.png" class="custom-block-image img-fluid" alt="">
                                                </a>
                                            </div>
                                        </div>

                                        <div class="col-lg-6 col-md-6 col-12">
                                            <div class="custom-block bg-white shadow-lg">
                                                <a href="#">
                                                    <div class="d-flex">
                                                        <div>
                                                            <h5 class="mb-2">Critical Thinking</h5>

                                                            <p class="mb-0">Logical fallacies, argument analysis, decision-making frameworks</p>
                                                        </div>

                                                        <span class="badge bg-education rounded-pill ms-auto">75</span>
                                                    </div>

                                                    <img src="images/topics/undraw_Educator_re_ju47.png" class="custom-block-image img-fluid" alt="">
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                    </div>
                </div>
            </section>


            <section class="timeline-section section-padding" id="section_3">
                <div class="section-overlay"></div>

                <div class="container">
                    <div class="row">

                        <div class="col-12 text-center">
                            <h2 class="text-white mb-4">How does it work?</h1>
                        </div>

                        <div class="col-lg-10 col-12 mx-auto">
                            <div class="timeline-container">
                                <ul class="vertical-scrollable-timeline" id="vertical-scrollable-timeline">
                                    <div class="list-progress">
                                        <div class="inner"></div>
                                    </div>

                                    <li>
                                        <h4 class="text-white mb-3">Tell the AI What You Want to Learn</h4>

                                        <p class="text-white">Just type in a topic, theme, or even a short description of what you need to study. Whether it’s “Photosynthesis,” “World War II causes,” or “Intro to Calculus,” the AI understands your intent and instantly gets to work. No need to dig through textbooks or Google for hours—just ask.</p>

                                        <div class="icon-holder">
                                          <i class="bi-search"></i>
                                        </div>
                                    </li>
                                    
                                    <li>
                                        <h4 class="text-white mb-3">Get Instant Study Materials</h4>

                                        <p class="text-white">Based on your input, the AI generates easy-to-understand explanations, key points, and summaries tailored to your learning level. You’ll receive focused content that actually helps you learn—no fluff, no filler. It’s like having a personal tutor that adapts to what you need.</p>

                                        <div class="icon-holder">
                                          <i class="bi-bookmark"></i>
                                        </div>
                                    </li>

                                    <li>
                                        <h4 class="text-white mb-3">Practice with Quizzes &amp; Flashcards</h4>

                                        <p class="text-white">Learning doesn’t stop with reading. Test yourself using AI-generated quizzes and smart flashcards that reinforce what you just learned. The more you study, the better the AI gets at personalizing your materials—helping you remember more, faster.</p>

                                        <div class="icon-holder">
                                          <i class="bi-book"></i>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        
                    </div>
                </div>
            </section>


            <section class="faq-section section-padding" id="section_4">
                <div class="container">
                    <div class="row">

                        <div class="col-lg-6 col-12">
                            <h2 class="mb-4">Frequently Asked Questions</h2>
                        </div>

                        <div class="clearfix"></div>

                        <div class="col-lg-5 col-12">
                            <img src="images/faq_graphic.jpg" class="img-fluid" alt="FAQs">
                        </div>

                        <div class="col-lg-6 col-12 m-auto">
                            <div class="accordion" id="accordionExample">
                                <div class="accordion-item special-accordion-item">
    <h2 class="accordion-header" id="headingOne">
        <button class="accordion-button collapsed special-accordion-btn" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
            What kinds of topics can I study with this AI?
        </button>
    </h2>
    <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
        <div class="accordion-body">
            You can study virtually anything! From school subjects like biology, history, and math, to broader topics like personal finance, coding, or even specific exam prep. Just type in what you want to learn, and the AI will generate content tailored to it.
        </div>
    </div>
</div>

                                <div class="accordion-item lighter-accordion-item">
                                    <h2 class="accordion-header" id="headingTwo">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                        How accurate is the information generated?
                                    </button>
                                    </h2>

                                    <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
                                        <div class="accordion-body">
                                            The AI pulls from a wide base of reliable sources and is designed to provide accurate, up-to-date, and easy-to-understand explanations. However, it's always good to double-check with official materials for high-stakes exams or specialized content.
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item lightest-accordion-item">
                                    <h2 class="accordion-header" id="headingThree">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                        Is this tool free to use?
                                    </button>
                                    </h2>

                                    <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordionExample">
                                        <div class="accordion-body">
                                            We offer a free basic version with full access to learning materials, quizzes, and flashcards. Premium plans with extra features (like progress tracking or advanced customization) may be available in the future as we grow.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </section>


            <section class="contact-section section-padding section-bg" id="section_5">
                <div class="container">
                    <div class="row">

                        <div class="col-lg-12 col-12 text-center">
                            <h2 class="mb-5">Get in touch</h2>
                        </div>

                        <div class="col-lg-5 col-12 mb-4 mb-lg-0">
                            <iframe class="google-map" src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d23473.182190548214!2d21.1177961743164!3d42.658223799999995!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x13549eeb8dca42eb%3A0x6e2e935d0eaa29c4!2sShkolla%20Digjitale%20%7C%20Digital%20School!5e0!3m2!1sen!2s!4v1748162918883!5m2!1sen!2s" width="100%" height="250" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                        </div>

                        <div class="col-lg-3 col-md-6 col-12 mb-3 mb-lg- mb-md-0 ms-auto">
                            <h4 class="mb-3">Head office</h4>

                            <p>Shkolla Digjitale | Digital School</p>

                            <hr>

                            <p class="d-flex align-items-center mb-1">
                                <span class="me-2">Phone</span>

                                <a href="tel: 305-240-9671" class="site-footer-link">
                                    305-240-9671
                                </a>
                            </p>

                            <p class="d-flex align-items-center">
                                <span class="me-2">Email</span>

                                <a href="mailto:info@company.com" class="site-footer-link">
                                    info@company.com
                                </a>
                            </p>
                        </div>

                        <div class="col-lg-3 col-md-6 col-12 mx-auto">
                            <h4 class="mb-3">Prishtina office</h4>

                            <p>Pashko Vasa, nr.28, Pejton, Prishtina 10000</p>

                            <hr>

                            <p class="d-flex align-items-center mb-1">
                                <span class="me-2">Phone</span>

                                <a href="tel: 110-220-3400" class="site-footer-link">
                                    110-220-3400
                                </a>
                            </p>

                            <p class="d-flex align-items-center">
                                <span class="me-2">Email</span>

                                <a href="mailto:info@company.com" class="site-footer-link">
                                    info@company.com
                                </a>
                            </p>
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

                    <div class="col-lg-3 col-md-4 col-6">
                        <h6 class="site-footer-title mb-3">Resources</h6>

                        <ul class="site-footer-links">
                            <li class="site-footer-link-item">
                                <a href="#" class="site-footer-link">Home</a>
                            </li>

                            <li class="site-footer-link-item">
                                <a href="#" class="site-footer-link">How it works</a>
                            </li>

                            <li class="site-footer-link-item">
                                <a href="#" class="site-footer-link">FAQs</a>
                            </li>

                            <li class="site-footer-link-item">
                                <a href="#" class="site-footer-link">Contact</a>
                            </li>
                        </ul>
                    </div>

                    <div class="col-lg-3 col-md-4 col-6 mb-4 mb-lg-0">
                        <h6 class="site-footer-title mb-3">Information</h6>

                        <p class="text-white d-flex mb-1">
                            <a href="tel: 305-240-9671" class="site-footer-link">
                                305-240-9671
                            </a>
                        </p>

                        <p class="text-white d-flex">
                            <a href="mailto:info@company.com" class="site-footer-link">
                                info@company.com
                            </a>
                        </p>
                    </div>

                    <div class="col-lg-3 col-md-4 col-12 mt-4 mt-lg-0 ms-auto">
                        <div class="dropdown">
                            <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            English</button>

                            <ul class="dropdown-menu">
                                <li><button class="dropdown-item" type="button">Thai</button></li>

                                <li><button class="dropdown-item" type="button">Myanmar</button></li>

                                <li><button class="dropdown-item" type="button">Arabic</button></li>
                            </ul>
                        </div>

                        <p class="copyright-text mt-lg-5 mt-4">Copyright © 2048 Apollo AI Listing Center. All rights reserved.</p>
                        
                        
                    </div>

                </div>
            </div>
        </footer>


        <script>
    document.getElementById('prompt-form').addEventListener('submit', async e => {
        e.preventDefault();
        const prompt = document.getElementById('prompt-input').value.trim();
        if (!prompt) return;

        const outEl = document.getElementById('output');
        const errEl = document.getElementById('error');
        const dbgEl = document.getElementById('debug');
        outEl.style.display = errEl.style.display = dbgEl.style.display = 'none';

        outEl.textContent = 'Loading…';
        outEl.style.display = 'block';

        try {
            const resp = await fetch('index.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ prompt })
            });
            const json = await resp.json();

            if (json.error) {
                outEl.style.display = 'none';
                errEl.textContent = json.error;
                errEl.style.display = 'block';
                if (json.raw_response) {
                    dbgEl.textContent = json.raw_response;
                    dbgEl.style.display = 'block';
                }
            } else {
                $('#output').html(marked.parse(json.result));
if (window.MathJax) MathJax.typesetPromise([document.getElementById('output')]);

                
                const topic = document.getElementById('prompt-input').value.trim();

fetch(`https://pixabay.com/api/?key=50494584-87d85c2a13020ebc5144f3a07&q=${encodeURIComponent(topic)}&image_type=photo&per_page=3&safesearch=true`)
  .then(resp => resp.json())
  .then(data => {
    if (data.hits && data.hits.length > 0) {
      document.getElementById('topic-img-tag').src = data.hits[0].webformatURL;
      document.getElementById('topic-image').style.display = 'block';
    } else {
      document.getElementById('topic-image').style.display = 'none';
    }
  })
  .catch(() => {
    document.getElementById('topic-image').style.display = 'none';
  });

                
                const readMoreBtn = document.querySelector('.btn.custom-btn');
                readMoreBtn.addEventListener('click', () => {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'main.php';

                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'conversation_data';
                    input.value = json.conversation_data;

                    form.appendChild(input);
                    document.body.appendChild(form);
                    form.submit();
                });
            }
        } catch (err) {
            outEl.style.display = 'none';
            errEl.textContent = 'Fetch error: ' + err.message;
            errEl.style.display = 'block';
        }
    });
  </script>
        <script src="js/jquery.min.js"></script>
        <script src="js/bootstrap.bundle.min.js"></script>
        <script src="js/jquery.sticky.js"></script>
        <script src="js/click-scroll.js"></script>
        <script src="js/custom.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
        <script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
<script id="MathJax-script" async
  src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
    </body>
</html>
