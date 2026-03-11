<?php
/**
 * Application Configuration
 */

// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'wikifeet_gta');
define('DB_USER', 'root');
define('DB_PASS', '');        // XAMPP default; Laragon may also be empty
define('DB_CHARSET', 'utf8mb4');

// Site
define('SITE_TITLE', 'GTA V Character Gallery');
define('SITE_URL', 'http://localhost/wikifeet');   // no trailing slash
define('ITEMS_PER_PAGE', 24);

// Donate link (PayPal, Ko-fi, etc.)
define('DONATE_URL', 'https://ko-fi.com/yourusername');

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
