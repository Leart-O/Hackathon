<?php
/**
 * Database diagnostic script
 */
require 'db.php';

echo "=== Database Diagnostic ===\n\n";

// 1. Check connection
echo "1. Connection: ✓ Connected\n\n";

// 2. Check users table exists
echo "2. Checking users table...\n";
try {
    $result = $db->query("SHOW TABLES LIKE 'users'")->fetch();
    if ($result) {
        echo "   ✓ users table exists\n\n";
    } else {
        echo "   ✗ users table NOT FOUND\n\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n\n";
}

// 3. List all columns in users table
echo "3. Columns in users table:\n";
try {
    $columns = $db->query("DESCRIBE users")->fetchAll();
    foreach ($columns as $col) {
        echo "   - " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n\n";
}

// 4. Count users
echo "4. Total users in database:\n";
try {
    $count = $db->query("SELECT COUNT(*) as cnt FROM users")->fetch();
    echo "   Count: " . $count['cnt'] . "\n\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n\n";
}

// 5. List all users
echo "5. All users:\n";
try {
    $users = $db->query("SELECT id, username, email, huggingface_token, huggingface_model FROM users")->fetchAll();
    if (count($users) > 0) {
        foreach ($users as $user) {
            echo "   ID: " . $user['id'] . "\n";
            echo "   Username: " . $user['username'] . "\n";
            echo "   Email: " . $user['email'] . "\n";
            echo "   Token: " . (isset($user['huggingface_token']) && $user['huggingface_token'] ? substr($user['huggingface_token'], 0, 10) . "..." : "EMPTY") . "\n";
            echo "   Model: " . (isset($user['huggingface_model']) && $user['huggingface_model'] ? $user['huggingface_model'] : "EMPTY") . "\n";
            echo "\n";
        }
    } else {
        echo "   ✗ No users found\n\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n\n";
}

// 6. Test prepared statement with user ID 1
echo "6. Testing prepared statement with user ID 1:\n";
try {
    $stmt = $db->prepare("SELECT huggingface_token, huggingface_model FROM users WHERE id = ?");
    echo "   Statement prepared: ✓\n";
    
    $stmt->execute([1]);
    echo "   Statement executed: ✓\n";
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   Result: " . (is_array($result) ? "Array found" : "FALSE - User not found") . "\n";
    
    if (is_array($result)) {
        var_dump($result);
    }
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}
?>
