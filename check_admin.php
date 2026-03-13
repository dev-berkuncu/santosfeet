<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';

try {
    $pdo = get_pdo();
    $stmt = $pdo->query("SELECT id, username, password_hash, created_at FROM admins");
    $admins = $stmt->fetchAll();
    
    if (empty($admins)) {
        echo "NO_ADMINS_FOUND\n";
    } else {
        echo "ADMINS_FOUND:\n";
        print_r($admins);
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
