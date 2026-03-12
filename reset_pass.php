<?php
require_once __DIR__ . '/lib/db.php';

$pdo = get_pdo();

// Kullanıcı adı ve şifre
$username = 'admin';
$password = '123456';
$hash = password_hash($password, PASSWORD_DEFAULT);

// Sadece bir tane admin hesabı olduğu için direkt güncelliyoruz
$stmt = $pdo->prepare("UPDATE admins SET username = ?, password_hash = ? WHERE id = 1");
$stmt->execute([$username, $hash]);

echo "Sifre basariyla güncellendi! Yeni Bilgiler:\n";
echo "Kullanıcı Adı: " . $username . "\n";
echo "Şifre: " . $password . "\n";
?>
