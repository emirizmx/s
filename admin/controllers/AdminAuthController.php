<?php
class AdminAuthController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function login() {
        // Admin login sayfasından normal login'e yönlendir
        if (!headers_sent()) {
            header('Location: /login');
            exit;
        } else {
            echo '<script>window.location.href = "/login";</script>';
            exit;
        }
    }
    
    public function logout() {
        // Session zaten başlatılmış mı kontrol et
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        // Session'ı temizle
        $_SESSION = array();
        
        // Session cookie'sini sil
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time()-3600, '/');
        }
        
        // Session'ı yok et
        session_destroy();
        
        // Yönlendirme
        if (!headers_sent()) {
            header('Location: /admin/login');
            exit;
        } else {
            echo '<script>window.location.href = "/admin/login";</script>';
            exit;
        }
    }
} 