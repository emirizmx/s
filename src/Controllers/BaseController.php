<?php
class BaseController {
    protected $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        
        // Her controller çağrıldığında session'ı kontrol et
        // Login ve register sayfalarında kontrol etme
        $route = $_GET['route'] ?? 'home';
        if (isset($_SESSION['user_id']) && !in_array($route, ['login', 'register', 'logout'])) {
            AuthMiddleware::validateUserSession();
        }
    }
} 