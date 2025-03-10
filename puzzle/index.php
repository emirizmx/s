<?php
// Eski session cookie'sini temizle
if (isset($_COOKIE['DH_SESSION'])) {
    setcookie('DH_SESSION', '', time() - 3600, '/', '.dijitalhediye.com', true, true);
}

// Hata raporlama ayarları
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/home/dijitzwc/public_html/puzzle/logs/error.log');

// Önce gerekli dosyaları include et
require_once 'config/config.php';    // Session burada başlatılacak
require_once 'config/database.php';   // Database sınıfı burada
require_once 'config/youtube.php';

// API hata yönetimi için yardımcı fonksiyon
function handleApiError($message, $code = 500) {
    header('Content-Type: application/json');
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message
    ]);
    exit;
}

// API isteklerinde JSON header'ı ekle
if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
    header('Content-Type: application/json');
}

// Debug için session durumunu logla
if (isset($_SESSION)) {
    error_log('Session durumu: ' . print_r($_SESSION, true));
}

// Veritabanı bağlantısını test et
try {
    $db = Database::getInstance()->getConnection();
    $db->query('SELECT 1');
    error_log("Veritabanı bağlantısı başarılı");
} catch (Exception $e) {
    error_log('Veritabanı bağlantı hatası: ' . $e->getMessage());
    if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
        handleApiError('Veritabanı bağlantısı kurulamadı');
    }
}

// Oyun aktiflik kontrolü
$stmt = $db->prepare("SELECT is_active FROM dh_games WHERE route = 'puzzle'");
$stmt->execute();
$game = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$game || $game['is_active'] == 0) {
    // Oyun pasif ise inactive.php sayfasına yönlendir
    require $_SERVER['DOCUMENT_ROOT'] . '/includes/inactive.php';
    exit;
}

// Log dosyasının konumunu öğren
$logFile = ini_get('error_log');
error_log("Log dosyası konumu: " . $logFile);

// Test log mesajı
error_log("Test log mesajı - " . date('Y-m-d H:i:s'));

// Base URL tanımı
define('BASE_URL', '/puzzle');

// Token kontrolü
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $db = Database::getInstance()->getConnection();
    
    try {
        // Token'ı ve session verilerini kontrol et
        $stmt = $db->prepare("
            SELECT u.*, u.last_session_data 
            FROM dh_users u 
            WHERE u.puzzle_token = ? 
            AND u.puzzle_token_expires > NOW()
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && $user['last_session_data']) {
            // Session verilerini geri yükle
            $sessionData = json_decode($user['last_session_data'], true);
            foreach ($sessionData as $key => $value) {
                $_SESSION[$key] = $value;
            }
            
            // Token'ı temizle
            $stmt = $db->prepare("
                UPDATE dh_users 
                SET puzzle_token = NULL, 
                    puzzle_token_expires = NULL,
                    last_session_data = NULL
                WHERE id = ?
            ");
            $stmt->execute([$user['id']]);
            
            // Puzzle sayfasına yönlendir
            header('Location: /puzzle/play');
            exit;
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
    }
}

try {
    // Route'ları işle
    $route = $_GET['route'] ?? 'home';
    
    // API isteklerini işle
    if (strpos($route, 'api/') === 0) {
        require_once 'src/Controllers/ApiController.php';
        $controller = new ApiController();
        
        $apiRoute = substr($route, 4); // "api/" kısmını kaldır
        
        switch($apiRoute) {
            case 'check-moves':
                $controller->checkMoves();
                break;
            
            case 'save-move':
                $controller->saveMove();
                break;
            
            case 'save-time':
                $controller->saveTime();
                break;
            
            case 'get-time':
                $controller->getTime();
                break;
            
            case 'toggle-pause':
                $controller->togglePause();
                break;
            
            case 'reset-puzzle':
                $controller->resetPuzzle();
                break;
            
            case 'get-puzzle-state':
                $controller->getPuzzleState();
                break;
            
            case 'save-score':
                $controller->saveScore();
                break;
            
            case 'get-image-path':
                $controller->getImagePath();
                break;
            
            case 'update-puzzle-visibility':
                $controller->updatePuzzleVisibility();
                break;
            
            case 'delete-moves':
                $controller->deleteMoves();
                break;
            
            case 'update-completion-message':
                $controller->updateCompletionMessage();
                break;
            
            case 'check-youtube-url':
                $controller->checkYoutubeUrl();
                break;
            
            default:
                http_response_code(404);
                echo json_encode(['error' => 'API endpoint bulunamadı']);
                break;
        }
        exit;
    }

    // Normal sayfa isteklerini işle
    switch($route) {
        case 'login':
            require_once 'src/Controllers/AuthController.php';
            $controller = new AuthController();
            $controller->login();
            break;
        
        case 'register':
            require_once 'src/Controllers/AuthController.php';
            $controller = new AuthController();
            $controller->register();
            break;
        
        case 'logout':
            require_once 'src/Controllers/AuthController.php';
            $controller = new AuthController();
            $controller->logout();
            break;
        
        case 'home':
            // Ana sayfa için session kontrolü gerekli
            if (!isset($_SESSION['user_id'])) {
                header('Location: ' . MAIN_SITE_URL . '/login');
                exit;
            }
            require_once 'src/Controllers/HomeController.php';
            $controller = new HomeController();
            $controller->index();
            break;
        
        case 'upload':
            // Upload için session kontrolü gerekli
            if (!isset($_SESSION['user_id'])) {
                header('Location: ' . MAIN_SITE_URL . '/login');
                exit;
            }
            require_once 'src/Controllers/PuzzleController.php';
            $controller = new PuzzleController();
            $controller->upload();
            break;
        
        case (preg_match('/^play\/([a-f0-9]{64})$/', $route, $matches) ? true : false):
            require_once 'src/Controllers/GameController.php';
            $controller = new GameController();
            $_GET['token'] = $matches[1];
            $controller->play();
            break;
        
        case 'play':
            require_once 'src/Controllers/GameController.php';
            $controller = new GameController();
            $controller->play();
            break;
        
        default:
            // 404 sayfası yerine invalid puzzle sayfasını göster
            if (strpos($route, 'play/') === 0) {
                require_once 'src/Controllers/GameController.php';
                $controller = new GameController();
                $message = 'Geçersiz puzzle bağlantısı. Lütfen doğru bir bağlantı kullandığınızdan emin olun.';
                require 'src/Views/errors/invalid-puzzle.php';
                exit;
            }
            
            header("HTTP/1.0 404 Not Found");
            echo "Sayfa bulunamadı";
            break;
    }
} catch (Exception $e) {
    if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
        // API istekleri için JSON hata yanıtı
        echo json_encode([
            'error' => 'Bir hata oluştu',
            'message' => $e->getMessage()
        ]);
    } else {
        // Normal sayfa istekleri için hata sayfası
        $_SESSION['error'] = $e->getMessage();
        header('Location: /puzzle/error');
    }
} 