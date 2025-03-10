<?php
class AuthMiddleware {
    public static function isLoggedIn() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in'])) {
            self::redirectToLogin();
        }

        // Kullanıcı bilgilerini veritabanından kontrol et
        self::validateUserSession();
    }
    
    public static function isGuest() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (isset($_SESSION['user_id']) && isset($_SESSION['logged_in'])) {
            // Eğer admin ise ve admin alanındaysa yönlendirme yapma
            if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] && 
                strpos($_SERVER['REQUEST_URI'], '/admin') === 0) {
                return;
            }
            
            header('Location: /');
            exit;
        }
    }

    public static function validateUserSession() {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                SELECT id, is_admin, is_active, username, email, credits
                FROM dh_users 
                WHERE id = ?
            ");
            
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                // Kullanıcı veritabanından silinmiş
                self::logout();
                self::redirectToLogin('Hesabınız bulunamadı.');
            }
            
            // Kullanıcı durumu değişmiş mi kontrol et
            if (!$user['is_active']) {
                self::logout();
                self::redirectToLogin('Hesabınız pasif duruma alınmış. Lütfen yönetici ile iletişime geçin.');
            }
            
            // Admin yetkisi değişmiş mi kontrol et
            if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] !== $user['is_admin']) {
                if (strpos($_SERVER['REQUEST_URI'], '/admin') === 0) {
                    self::logout();
                    self::redirectToLogin('Admin yetkiniz kaldırılmış.');
                } else {
                    // Session'ı güncelle
                    $_SESSION['is_admin'] = $user['is_admin'];
                }
            }
            
            // Diğer session bilgilerini güncelle
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['credits'] = $user['credits'];
            $_SESSION['is_active'] = $user['is_active'];
            
        } catch (PDOException $e) {
            error_log('Session validation error: ' . $e->getMessage());
            // Veritabanı hatası durumunda sessiona dokunma
        }
    }

    private static function redirectToLogin($message = null) {
        if ($message) {
            $_SESSION['error'] = $message;
        }
        
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            http_response_code(401);
            echo json_encode(['error' => $message ?? 'Oturum süresi doldu. Lütfen tekrar giriş yapın.']);
            exit;
        }
        
        header('Location: /login');
        exit;
    }

    public static function logout() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        $_SESSION = array();
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time()-3600, '/');
        }
        
        session_destroy();
    }
} 