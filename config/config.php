<?php
/**
 * ResultPadi - Central configuration
 */

error_reporting(E_ALL);
ini_set('display_errors', '1'); // Set to '0' in production later

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Africa/Lagos');

define('APP_NAME', 'ResultPadi');
define('APP_VERSION', '1.0.0');
define('APP_ROOT', dirname(__DIR__));

// --- DATABASE CREDENTIALS (Fallbacks for local dev) ---
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'resultpadi_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// --- DYNAMIC BASE URL (Prioritizes ZevCloud environment variable) ---
if (getenv('APP_URL')) {
    define('BASE_URL', rtrim(getenv('APP_URL'), '/') . '/');
} else {
    // Fallback for local development
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'], '/\\')) : '';
    $appRoot = str_replace('\\', '/', APP_ROOT);
    $basePath = '';

    if ($docRoot !== '' && $docRoot !== '/') {
        if (stripos($appRoot, $docRoot) === 0) {
            $basePath = substr($appRoot, strlen($docRoot));
        }
    }
    $basePath = '/' . trim(str_replace('\\', '/', $basePath), '/');
    if ($basePath === '/') $basePath = '';

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('BASE_URL', $scheme . '://' . $host . $basePath . '/');
}