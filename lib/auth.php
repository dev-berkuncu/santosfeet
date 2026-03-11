<?php
/**
 * Authentication helpers
 */
require_once __DIR__ . '/../config.php';

function is_logged_in(): bool {
    return !empty($_SESSION['admin_id']);
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: ' . SITE_URL . '/admin/login.php');
        exit;
    }
}

function login_admin(int $id, string $username): void {
    $_SESSION['admin_id'] = $id;
    $_SESSION['admin_username'] = $username;
    session_regenerate_id(true);
}

function logout_admin(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']
        );
    }
    session_destroy();
}
