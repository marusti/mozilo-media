<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

const APP_VERSION = '0.10.0-alpha';
const DATA_DIR = __DIR__ . '/data';
const UPLOAD_DIR = __DIR__ . '/uploads';
const MAX_UPLOAD_BYTES = 10 * 1024 * 1024;
const CMS_VERSIONS = [
    '2.0',
    '3.x',
];
const NEW_FILE_DAYS = 7;
const SESSION_TIMEOUT = 1800; // 30 Minuten

$lang = require __DIR__ . '/lang/de.php';

function lang(string $key): string
{
    global $lang;

    return $lang[$key] ?? $key;
}

/*
 * Session-Konfiguration
 *
 * Lokal:
 *   http://localhost -> Secure wird deaktiviert
 *
 * Produktion:
 *   https://example.de -> Secure wird aktiviert
 */

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

if (session_status() !== PHP_SESSION_ACTIVE) {

    $isHttps = !empty($_SERVER['HTTPS'])
        && $_SERVER['HTTPS'] !== 'off';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}


foreach ([DATA_DIR, UPLOAD_DIR] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0750, true);
    }
}
