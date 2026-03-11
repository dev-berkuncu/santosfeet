<?php
/**
 * Admin Logout
 */
require_once __DIR__ . '/../lib/auth.php';
logout_admin();
header('Location: ' . SITE_URL . '/admin/login.php');
exit;
