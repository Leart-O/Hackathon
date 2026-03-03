<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Fetch current token
$stmt = $db->prepare("SELECT huggingface_token FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$current_token = $user['huggingface_token'] ?? '';

// Handle token update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['huggingface_token'])) {
    $token = trim($_POST['huggingface_token']);
    
    if (empty($token)) {
        $error = 'API key cannot be empty.';
    } else {
        $stmt = $db->prepare("UPDATE users SET huggingface_token = ? WHERE id = ?");
        if ($stmt->execute([$token, $user_id])) {
            $message = 'Hugging Face API token updated successfully!';
            $current_token = $token;
        } else {
            $error = 'Error updating API key.';
        }
    }
}

// Handle model configuration
$stmt = $db->prepare("SELECT huggingface_model FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$current_model = $user['huggingface_model'] ?? 'gpt2';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['huggingface_model'])) {
    $model = trim($_POST['huggingface_model']);
    
    if (empty($model)) {
        $error = 'Model cannot be empty.';
    } else {
        $stmt = $db->prepare("UPDATE users SET huggingface_model = ? WHERE id = ?");
        if ($stmt->execute([$model, $user_id])) {
            $message = 'Model updated successfully!';
            $current_model = $model;
        } else {
            $error = 'Error updating model.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Settings</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding-top: 80px;
        }
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .settings-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 30px 0;
        }
        .settings-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 20px;
        }
        .settings-card h3 {
            margin-bottom: 20px;
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .form-group label {
            font-weight: 600;
            color: #333;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-save {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            font-weight: 600;
            transition: transform 0.2s;
        }
        .btn-save:hover {
            transform: translateY(-2px);
            color: white;
        }
        .btn-back {
            background: transparent;
            border: 2px solid #667eea;
            color: #667eea;
            font-weight: 600;
            margin-top: 20px;
        }
        .btn-back:hover {
            background: #667eea;
            color: white;
        }
        .alert {
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body id="top">
<main>
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="main.php">
                <i class="bi-arrow-left"></i>
                <span>Apollo AI - Settings</span>
            </a>
        </div>
    </nav>

    <section class="settings-container">
        <?php if (!empty($message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="settings-card">
            <h3><i class="bi bi-key"></i> Hugging Face Token</h3>
            <form method="POST">
                <div class="form-group mb-3">
                    <label for="huggingface_token">API Token</label>
                    <input type="password" name="huggingface_token" id="huggingface_token" class="form-control" 
                           value="<?php echo htmlspecialchars($current_token); ?>" placeholder="Enter your Hugging Face API token">
                    <small class="text-muted d-block mt-2">
                        Get your token from <a href="https://huggingface.co/settings/tokens" target="_blank">Hugging Face Settings</a>
                    </small>
                </div>
                <button type="submit" class="btn btn-save w-100">Save Token</button>
            </form>
        </div>

        <div class="settings-card">
            <h3><i class="bi bi-gear"></i> Model Selection</h3>
            <form method="POST">
                <div class="form-group mb-3">
                    <label for="huggingface_model">Model ID</label>
                    <input type="text" name="huggingface_model" id="huggingface_model" class="form-control" 
                           value="<?php echo htmlspecialchars($current_model); ?>" placeholder="e.g., Qwen/Qwen2.5-7B-Instruct">
                    <small class="text-muted d-block mt-2">
                        Find models at <a href="https://huggingface.co/models?pipeline_tag=text-generation" target="_blank">Hugging Face Models</a>.
                        Popular: Qwen/Qwen2.5-7B-Instruct, mistralai/Mistral-7B-Instruct-v0.2, meta-llama/Llama-2-7b-chat
                    </small>
                </div>
                <button type="submit" class="btn btn-save w-100">Save Model</button>
            </form>
        </div>

        <a href="main.php" class="btn btn-back w-100">Back to Chat</a>
    </section>
</main>

<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>
