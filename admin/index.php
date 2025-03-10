<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once __DIR__ . '/middleware/AdminAuthMiddleware.php';

// Controllers
require_once __DIR__ . '/controllers/AdminAuthController.php';
require_once __DIR__ . '/controllers/AdminDashboardController.php';
require_once __DIR__ . '/controllers/AdminUsersController.php';
require_once __DIR__ . '/controllers/AdminCreditsController.php';
require_once __DIR__ . '/controllers/AdminTransactionsController.php';
require_once __DIR__ . '/controllers/AdminGamesController.php';
require_once __DIR__ . '/controllers/AdminPromptsController.php';
require_once __DIR__ . '/controllers/AdminLogsController.php';  // Yeni eklenen controller

// Route kontrolü
$validRoutes = [
    'dashboard',
    'users',
    'users/edit',
    'credits/packages',
    'credits/packages/edit',
    'credits/transactions',
    'games',
    'games/edit',
    'prompts',
    'prompts/edit',
    'logs/login',  // Yeni eklenen route
    'logs/login/detail',  // Yeni eklenen route
    'logs/system',  // Yeni eklenen route
    'logs/system/detail'  // Yeni eklenen route
];

$route = $_GET['route'] ?? 'dashboard';

// Route'u kontrol et
if (!in_array($route, $validRoutes)) {
    http_response_code(404);
    require 'views/errors/404.php';
    exit;
}

// Admin authentication kontrolü
if ($route !== 'login' && $route !== 'logout') {
    AdminAuthMiddleware::isAdmin();
}

// Routing işlemleri
try {
    switch($route) {
        case 'login':
            AdminAuthMiddleware::isGuest();
            $controller = new AdminAuthController();
            $controller->login();
            break;
            
        case 'logout':
            $controller = new AdminAuthController();
            $controller->logout();
            break;
            
        case 'dashboard':
            $controller = new AdminDashboardController();
            $controller->index();
            break;
            
        case 'users':
            $controller = new AdminUsersController();
            $controller->index();
            break;
            
        case 'users/edit':
            $controller = new AdminUsersController();
            $controller->edit();
            break;
            
        case 'credits/packages':
            $controller = new AdminCreditsController();
            $controller->packages();
            break;
            
        case 'credits/packages/edit':
            $controller = new AdminCreditsController();
            $controller->editPackage();
            break;
            
        case 'credits/transactions':
            $controller = new AdminTransactionsController();
            $controller->index();
            break;
            
        case 'credits/transactions/update-status':
            $controller = new AdminTransactionsController();
            $controller->updateStatus();
            break;
            
        case 'games':
            $controller = new AdminGamesController();
            $controller->index();
            break;
            
        case 'games/edit':
            $controller = new AdminGamesController();
            $controller->edit();
            break;
            
        case 'prompts':
            $controller = new AdminPromptsController();
            $controller->index();
            break;
            
        case 'prompts/edit':
            $controller = new AdminPromptsController();
            $controller->edit();
            break;
            
        case 'logs/login':
            $controller = new AdminLogsController();
            $controller->loginLogs();
            break;
            
        case 'logs/login/detail':
            $controller = new AdminLogsController();
            $controller->loginLogDetail();
            break;
            
        case 'logs/system':
            $controller = new AdminLogsController();
            $controller->systemLogs();
            break;
            
        case 'logs/system/detail':
            $controller = new AdminLogsController();
            $controller->systemLogDetail();
            break;
            
        default:
            http_response_code(404);
            require 'views/errors/404.php';
            break;
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    $_SESSION['error'] = 'Bir hata oluştu';
    header('Location: /admin/dashboard');
    exit;
} 