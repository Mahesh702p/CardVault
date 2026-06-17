<?php
/**
 * Application Configuration
 * Works both locally (public/ as web root) and on Cloudways (public_html/ as web root)
 */

// ─── Base path = parent of the config/ folder ─────────────────────────────────
// Locally:     card-ai/config/ → BASE_PATH = card-ai/
// Production:  public_html/config/ → BASE_PATH = public_html/
define('BASE_PATH', dirname(__DIR__));

// ─── Load .env file ───────────────────────────────────────────────────────────
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '='))
            continue;
        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
    }
}

// ─── Public/upload/view paths ─────────────────────────────────────────────────
// Locally:     BASE_PATH has a 'public' subfolder → PUBLIC_PATH = BASE_PATH/public
// Production:  BASE_PATH IS the web root (no 'public' subfolder)
define('PUBLIC_PATH', is_dir(BASE_PATH . '/public') ? BASE_PATH . '/public' : BASE_PATH);
define('UPLOAD_PATH', PUBLIC_PATH . '/uploads/cards');
define('VIEW_PATH', BASE_PATH . '/views');

// ─── App settings ─────────────────────────────────────────────────────────────
define('APP_NAME', 'CardVault');
define('APP_VERSION', '1.0.0');

// Detect APP_URL dynamically if not set in .env
if (!empty($_ENV['APP_URL'])) {
    define('APP_URL', rtrim($_ENV['APP_URL'], '/'));
} else {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('APP_URL', $scheme . '://' . $host);
}

// ─── Gemini AI Configuration ──────────────────────────────────────────────────
define('GEMINI_API_KEY', $_ENV['GEMINI_API_KEY'] ?? '');

// ─── Upload settings ──────────────────────────────────────────────────────────
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/heic']);

// ─── Pagination ───────────────────────────────────────────────────────────────
define('ITEMS_PER_PAGE', 20);

// ─── Session settings ─────────────────────────────────────────────────────────
define('SESSION_LIFETIME', 3600 * 8); // 8 hours

// ─── Security ─────────────────────────────────────────────────────────────────
define('CSRF_TOKEN_NAME', 'csrf_token');
