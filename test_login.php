<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';

echo "Testing login...\n\n";

$username = 'admin';
$password = 'admin123';

try {
    $pdo = get_pdo();
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        echo "User 'admin' not found in database.\n";
    } else {
        echo "User found. ID: " . $admin['id'] . "\n";
        echo "Hash in DB: " . $admin['password_hash'] . "\n";
        
        if (password_verify($password, $admin['password_hash'])) {
            echo "Password verification SUCCESSFUL!\n";
        } else {
            echo "Password verification FAILED!\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
