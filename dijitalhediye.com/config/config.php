<?php
// Site ayarları
define('SITE_URL', 'https://dijitalhediye.com');
define('SITE_NAME', 'Dijital Hediye');
define('PUZZLE_URL', 'https://dijitalhediye.com/puzzle');

// Hata raporlama
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Zaman dilimi
date_default_timezone_set('Europe/Istanbul');

// Session ayarları
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_domain', '.dijitalhediye.com'); // Alt domain için
ini_set('session.cookie_path', '/');
ini_set('session.gc_maxlifetime', 3600); // 1 saat
session_name('DH_SESSION');

// Session başlat
session_start();

// CSRF token kontrolü
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token doğrulaması başarısız');
    }
}

// Karakter seti
ini_set('default_charset', 'UTF-8'); 