<?php
// Hata raporlama - Geliştirme sırasında hataları göster
error_reporting(E_ALL);
ini_set('display_errors', 1); // Hataları göster
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Session ayarları
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_domain', '.dijitalhediye.com');  // Ana domain
ini_set('session.cookie_path', '/');
ini_set('session.gc_maxlifetime', 3600);
session_name('dh_session'); // Ana site ile aynı session adını kullan

// Session başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug için session bilgilerini yazdır
error_log('=== Yeni istek başladı ===');
error_log('REQUEST_URI: ' . $_SERVER['REQUEST_URI']);
error_log('Session durumu: ' . print_r($_SESSION, true));
error_log('Cookie durumu: ' . print_r($_COOKIE, true));

// Site ayarları
define('SITE_URL', 'https://dijitalhediye.com');
define('MAIN_SITE_URL', 'https://dijitalhediye.com');
define('PUZZLE_URL', 'https://dijitalhediye.com/puzzle');

// Token ile giriş kontrolü
if (isset($_GET['token'])) {
    // Token varsa session kontrolünü atla
    return;
}

// CSRF token oluştur
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// CSRF kontrolü gerektiren route'lar
$csrf_routes = ['login', 'register', 'credits/purchase'];

// Sadece belirli route'lar için CSRF kontrolü yap
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_route = $_GET['route'] ?? 'home';
    if (in_array($current_route, $csrf_routes)) {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            $_SESSION['error'] = 'Güvenlik doğrulaması başarısız. Lütfen tekrar deneyin.';
            header('Location: /' . $current_route);
            exit;
        }
    }
}

// Karakter seti
ini_set('default_charset', 'UTF-8');

// Session kontrolü sadece gerekli durumlarda yapılacak
// Bu kontrolü kaldırıyoruz çünkü kontrolü GameController'da yapacağız
/*
if (!isset($_GET['token'])) {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in'])) {
        error_log('Oturum kontrolü başarısız: user_id=' . (isset($_SESSION['user_id']) ? 'var' : 'yok') . 
                 ', logged_in=' . (isset($_SESSION['logged_in']) ? 'var' : 'yok'));
        header('Location: ' . MAIN_SITE_URL . '/login');
        exit;
    }
}
*/ 