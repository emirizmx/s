<?php
class HomeController extends BaseController {
    public function __construct() {
        parent::__construct();
    }
    
    public function index() {
        // Oyunları veritabanından al
        $stmt = $this->db->query("
            SELECT 
                id,
                name,
                description,
                credits,
                voice_credits,
                is_active,
                route,
                image_path
            FROM dh_games 
            WHERE is_active = 1 
            ORDER BY display_order ASC
        ");
        $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Kullanıcı giriş yapmışsa kredi bilgisini al
        $userCredits = 0;
        if (isset($_SESSION['user_id'])) {
            $stmt = $this->db->prepare("SELECT credits FROM dh_users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $userCredits = $user['credits'] ?? 0;
            $_SESSION['credits'] = $userCredits;
        }
        
        require 'src/Views/home/index.php';
    }
} 