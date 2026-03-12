<?php
/**
 * Database connection via PDO
 */
require_once __DIR__ . '/../config.php';

function get_pdo(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $ex) {
            http_response_code(500);
            die(
                '<h1>Database Connection Error</h1>'
                . '<p><strong>Error:</strong> ' . htmlspecialchars($ex->getMessage()) . '</p>'
                . '<p>Please check your <code>config.php</code> database credentials (DB_HOST, DB_NAME, DB_USER, DB_PASS).</p>'
            );
        }
    }
    return $pdo;
}
