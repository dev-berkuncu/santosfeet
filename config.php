<?php
/**
 * Application Configuration
 */

// ── Debug (set to false after fixing the issue) ─────────────────────
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// ── Database ────────────────────────────────────────────────────────
// TODO: Update these with your hosting panel's MySQL credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'wikifeet_gta');           // TODO: your DB name from hosting panel
define('DB_USER', 'root');                   // TODO: your DB user from hosting panel
define('DB_PASS', '');                       // TODO: your DB password from hosting panel
define('DB_CHARSET', 'utf8mb4');

// Site
define('SITE_TITLE', 'Santosfeet');
define('SITE_URL', 'https://santosfeet.com');   // no trailing slash
define('ITEMS_PER_PAGE', 24);

// Donate link (PayPal, Ko-fi, etc.)
define('DONATE_URL', 'https://ko-fi.com/yourusername');

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
