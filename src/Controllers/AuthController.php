<?php
require_once 'src/Models/User.php';

class AuthController extends BaseController {
    private $userModel;
    
    public function __construct() {
        parent::__construct();
        $this->userModel = new User();
    }
    
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $username = $_POST['email'] ?? '';
                $password = $_POST['password'] ?? '';
                
                if (empty($username) || empty($password)) {
                    throw new Exception('Lütfen tüm alanları doldurun.');
                }
                
                $stmt = $this->db->prepare("
                    SELECT * FROM dh_users 
                    WHERE (username = ? OR email = ?) 
                    AND is_active = 1
                ");
                $stmt->execute([$username, $username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user && password_verify($password, $user['password_hash'])) {
                    // Kullanıcı aktif mi kontrol et
                    if (!$user['is_active']) {
                        throw new Exception('Hesabınız pasif durumdadır. Lütfen yönetici ile iletişime geçin.');
                    }

                    // Session'ı başlat
                    if (session_status() !== PHP_SESSION_ACTIVE) {
                        session_start();
                    }

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['credits'] = $user['credits'];
                    $_SESSION['is_admin'] = $user['is_admin'];
                    $_SESSION['is_active'] = $user['is_active'];
                    $_SESSION['logged_in'] = true;
                    
                    // CSRF token'ı yenile
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    
                    // Giriş loglaması eklendi
                    LogHelper::logLogin(
                        $user['id'],
                        $user['username'],
                        $user['email'],
                        true
                    );
                    
                    // Admin girişi ise admin paneline yönlendir
                    if ($user['is_admin'] && strpos($_SERVER['REQUEST_URI'], '/admin/login') !== false) {
                        header('Location: /admin/dashboard');
                        exit;
                    }
                    
                    // Normal kullanıcı girişi
                    header('Location: /');
                    exit;
                }
                
                // Başarısız giriş loglanır
                LogHelper::logLogin(
                    null,
                    $username,
                    null,
                    false,
                    'Geçersiz kullanıcı adı/e-posta veya şifre'
                );
                
                throw new Exception('Geçersiz kullanıcı adı/e-posta veya şifre.');
                
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
            }
        }
        
        require 'src/Views/auth/login.php';
    }
    
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $username = trim($_POST['username'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                $password = $_POST['password'] ?? '';
                $passwordConfirm = $_POST['password_confirm'] ?? '';
                
                if (empty($username) || empty($email) || empty($phone) || empty($password)) {
                    throw new Exception('Tüm alanları doldurunuz');
                }
                
                if (!preg_match('/^[0-9]{10,11}$/', preg_replace('/[^0-9]/', '', $phone))) {
                    throw new Exception('Geçerli bir telefon numarası giriniz');
                }
                
                $user = $this->userModel->getUserByEmail($email);
                
                if ($user) {
                    throw new Exception('Bu e-posta adresi zaten kullanılıyor.');
                }
                
                $userId = $this->userModel->register($username, $email, $password, $phone);
                
                $_SESSION['success'] = 'Kayıt başarılı! Şimdi giriş yapabilirsiniz.';
                header('Location: /login');
                exit;
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
            }
        }
        
        require 'src/Views/auth/register.php';
    }
    
    public function logout() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        // Session'ı temizle
        $_SESSION = array();
        
        // Session cookie'sini sil
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time()-3600, '/');
        }
        
        session_destroy();
        header('Location: /login');
        exit;
    }
} 