<?php
require 'db.php';

try {
    // Add huggingface_token column if it doesn't exist
    $db->exec("ALTER TABLE users ADD COLUMN huggingface_token VARCHAR(500) DEFAULT NULL");
    echo "Added huggingface_token column<br>";
} catch (PDOException $e) {
    echo "huggingface_token column might already exist: " . $e->getMessage() . "<br>";
}

try {
    // Add huggingface_model column if it doesn't exist
    $db->exec("ALTER TABLE users ADD COLUMN huggingface_model VARCHAR(255) DEFAULT 'gpt2'");
    echo "Added huggingface_model column<br>";
} catch (PDOException $e) {
    echo "huggingface_model column might already exist: " . $e->getMessage() . "<br>";
}

echo "Database migration completed!<br>";
echo "Please visit <a href='settings.php'>Settings</a> to add your Hugging Face API token.";
?>
