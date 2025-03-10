<?php
// Ana config'i önce dahil et (session ve database ayarları için)
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../src/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/src/Controllers/StoryController.php';

// URL'yi kontrol et
$requestUri = $_SERVER['REQUEST_URI'];
$pathInfo = parse_url($requestUri, PHP_URL_PATH);

// AJAX istekleri için özel route kontrolü
if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($pathInfo, '/story/share/') === 0) {
    $id = substr($pathInfo, strlen('/story/share/'));
    if (is_numeric($id)) {
        $controller = new StoryController();
        $controller->share($id);
        exit;
    }
}

// Paylaşılan hikaye görüntüleme
if (strpos($pathInfo, '/story/share/') === 0 && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = substr($pathInfo, strlen('/story/share/'));
    if (!empty($token)) {
        $controller = new StoryController();
        $controller->viewShared($token);
        exit;
    }
}

// View route'unu shared'a yönlendir
if (isset($_GET['action']) && $_GET['action'] === 'view') {
    $id = $_GET['id'] ?? null;
    if ($id) {
        $controller = new StoryController();
        // Story modelini public yaparak erişim sağla
        $story = $controller->getStory($id); // Yeni metod ekleyeceğiz
        if ($story && isset($story['share_token'])) {
            header('Location: /story/share/' . $story['share_token']);
            exit;
        }
    }
}

// Download route'u ekle
if (strpos($pathInfo, '/story/download/') === 0) {
    $parts = explode('/', substr($pathInfo, strlen('/story/download/')));
    $id = $parts[0];
    $token = $parts[1] ?? null;
    
    if (is_numeric($id)) {
        $controller = new StoryController();
        $controller->downloadAudio($id, $token);
        exit;
    }
}

// Normal story sayfaları için session kontrolü
if (!isset($_SESSION['user_id'])) {
    header('Location: /login');
    exit;
}

// Ana dizini tanımla
define('BASE_PATH', dirname(__DIR__));

// Gerekli dosyaları dahil et
require_once __DIR__ . '/config/gemini.php';
require_once __DIR__ . '/config/tts.php';

try {
    // Controller'ı başlat
    $controller = new StoryController();

    // URL'den action parametresini al
    if ($pathInfo == '/story/list') {
        $action = 'list';
    } else {
        $action = isset($_GET['action']) ? $_GET['action'] : 'index';
    }

    // İsteği yönlendir
    switch ($action) {
        case 'create':
            $controller->create();
            break;
        
        case 'list':
            $controller->list();
            break;
        
        case 'generateAudio':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                header('Location: /story');
                exit;
            }
            $controller->generateAudio($id);
            break;
        
        default:
            $controller->index();
            break;
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    $_SESSION['error'] = 'Bir hata oluştu';
    header('Location: /story');
    exit;
}