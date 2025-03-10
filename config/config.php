<?php
// Hata raporlama
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Site ayarları
define('SITE_NAME', 'Dijital Hediye');
define('SITE_URL', 'https://dijitalhediye.com');

// Session ayarları
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_domain', '.dijitalhediye.com');
ini_set('session.cookie_path', '/');
ini_set('session.gc_maxlifetime', 3600);
session_name('dh_session');

// Session başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug için session bilgilerini yazdır
error_log('=== Session Debug ===');
error_log('Current URL: ' . $_SERVER['REQUEST_URI']);
error_log('Session Status: ' . session_status());
error_log('Session ID: ' . session_id());
error_log('Session Name: ' . session_name());
error_log('Session Cookie: ' . ($_COOKIE[session_name()] ?? 'not set'));
error_log('User ID in Session: ' . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'not set'));

// Yeni session başlatıldığında log
if (!isset($_COOKIE[session_name()])) {
    error_log('Starting new session...');
    error_log('New Session ID: ' . session_id());
}

// URL kontrolü
error_log('Checking URL: ' . $_SERVER['REQUEST_URI']);

// Story URL ve Download URL kontrolü
$isStoryUrl = strpos($_SERVER['REQUEST_URI'], '/story/share/') === 0;
$isDownloadUrl = strpos($_SERVER['REQUEST_URI'], '/story/download/') === 0;
error_log('STORY_URL check: ' . ($isStoryUrl ? 'true' : 'false'));
error_log('DOWNLOAD_URL check: ' . ($isDownloadUrl ? 'true' : 'false'));

// Paylaşılan hikayeler ve indirme için session kontrolünü atla
if ($isStoryUrl || $isDownloadUrl) {
    error_log('Story/Download URL detected, skipping session check...');
    return;
}

// Session kontrolü (paylaşılan hikayeler hariç)
if (!isset($_SESSION['user_id'])) {
    $currentUrl = $_SERVER['REQUEST_URI'];
    $publicRoutes = [
        '/',             // Ana sayfa eklendi
        '/index.php',    // index.php ile erişim için
        '/login',
        '/register',
        '/logout',
        '/about',
        '/credits/notify',
        '/credits/success',
        '/credits/failed'
    ];
    
    if (!in_array($currentUrl, $publicRoutes)) {
        error_log('No user_id in session, redirecting to login');
        header('Location: /login');
        exit;
    }
}