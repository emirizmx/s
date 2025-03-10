<?php
class AuthController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function login() {
        // Ana sitenin giriş sayfasına yönlendir
        header('Location: https://dijitalhediye.com/login');
        exit;
    }
    
    public function register() {
        // Ana sitenin kayıt sayfasına yönlendir
        header('Location: https://dijitalhediye.com/register');
        exit;
    }
    
    public function logout() {
        session_destroy();
        // Ana sitenin giriş sayfasına yönlendir
        header('Location: https://dijitalhediye.com/login');
        exit;
    }
    
    // Ana siteden gelen kullanıcı bilgilerini doğrula ve puzzle sistemine aktar
    public function validateMainUser($mainUserId) {
        $stmt = $this->db->prepare("
            SELECT u.*, dh.credits 
            FROM dh_users dh 
            LEFT JOIN users u ON u.main_user_id = dh.id 
            WHERE dh.id = ?
        ");
        
        $stmt->execute([$mainUserId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['credits'] = $user['credits'];
            $_SESSION['main_user_id'] = $mainUserId;
            return true;
        }
        
        return false;
    }
} 