<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/db.php';

// Güvenli ve doğrudan bir bcrypt hash'i oluşturuyoruz.
$username = 'admin';
$password = 'admin123';
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

try {
    $pdo = get_pdo();
    
    // Varolan admin şifresini zorla güncelliyoruz.
    $stmt = $pdo->prepare("UPDATE admins SET password_hash = ? WHERE username = ?");
    $stmt->execute([$hash, $username]);
    
    // Doğrulama kontrolü.
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "<h1>Kayıt Güncellendi!</h1>";
        echo "Yeni Hash: " . $admin['password_hash'] . "<br><br>";
        
        if (password_verify($password, $admin['password_hash'])) {
            echo "<h2 style='color:green'>TEST BAŞARILI! Şifre artık doğrulandı.</h2>";
            echo "<p>Lütfen admin paneline gidip <strong>admin</strong> ve <strong>admin123</strong> bilgileriyle tekrar giriş yapmayı deneyin.</p>";
        } else {
            echo "<h2 style='color:red'>TEST BAŞARISIZ! PHP password_hash fonksiyonunda bir sorun olabilir.</h2>";
        }
    }
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage();
}
