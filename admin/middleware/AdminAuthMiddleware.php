<?php
class AdminAuthMiddleware {
    public static function isAdmin() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin'] || !isset($_SESSION['logged_in'])) {
            self::redirectToLogin();
        }

        // Admin yetkisini veritabanından kontrol et
        self::validateAdminSession();
    }
    
    public static function isGuest() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (isset($_SESSION['user_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] && isset($_SESSION['logged_in'])) {
            if (!headers_sent()) {
                header('Location: /admin/dashboard');
                exit;
            } else {
                echo '<script>window.location.href = "/admin/dashboard";</script>';
                exit;
            }
        }
    }

    private static function validateAdminSession() {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                SELECT is_admin, is_active 
                FROM dh_users 
                WHERE id = ?
            ");
            
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !$user['is_admin'] || !$user['is_active']) {
                self::logout();
                self::redirectToLogin('Admin yetkiniz bulunmuyor veya hesabınız pasif durumda.');
            }
            
        } catch (PDOException $e) {
            error_log('Admin session validation error: ' . $e->getMessage());
            // Veritabanı hatası durumunda sessiona dokunma
        }
    }

    private static function redirectToLogin($message = null) {
        if ($message) {
            $_SESSION['error'] = $message;
        }

        if (!headers_sent()) {
            header('Location: /admin/login');
            exit;
        } else {
            echo '<script>window.location.href = "/admin/login";</script>';
            exit;
        }
    }

    private static function logout() {
        $_SESSION = array();
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time()-3600, '/');
        }
        
        session_destroy();
    }
} 