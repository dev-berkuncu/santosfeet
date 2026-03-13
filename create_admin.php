<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';

$username = 'admin';
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $pdo = get_pdo();
    
    // Check if admin exists
    $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        // Update password
        $stmt = $pdo->prepare("UPDATE admins SET password_hash = ? WHERE username = ?");
        $stmt->execute([$hash, $username]);
        echo "<h1>Admin password updated successfully!</h1>";
        echo "<p>Username: <strong>admin</strong></p>";
        echo "<p>Password: <strong>admin123</strong></p>";
        echo "<p>Please delete this file for security.</p>";
    } else {
        // Create new admin
        $stmt = $pdo->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?)");
        $stmt->execute([$username, $hash]);
        echo "<h1>Admin created successfully!</h1>";
        echo "<p>Username: <strong>admin</strong></p>";
        echo "<p>Password: <strong>admin123</strong></p>";
        echo "<p>Please delete this file for security.</p>";
    }
} catch (Exception $e) {
    echo "<h1>Error</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
