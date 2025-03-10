<?php
class AdminGamesController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function index() {
        try {
            // Oyunları veritabanından al
            $stmt = $this->db->query("
                SELECT * FROM dh_games 
                ORDER BY display_order ASC
            ");
            $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $pageTitle = 'Oyunlar';
            require 'views/games/index.php';
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Oyunlar listelenirken bir hata oluştu: ' . $e->getMessage();
            header('Location: /admin');
            exit;
        }
    }
    
    public function edit() {
        try {
            $id = $_GET['id'] ?? null;
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->updateGame($_POST);
                $_SESSION['success'] = 'Oyun başarıyla güncellendi';
                header('Location: /admin/games');
                exit;
            }
            
            $stmt = $this->db->prepare("SELECT * FROM dh_games WHERE id = ?");
            $stmt->execute([$id]);
            $game = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$game) {
                throw new Exception('Oyun bulunamadı');
            }
            
            $pageTitle = 'Oyun Düzenle: ' . $game['name'];
            require 'views/games/edit.php';
            
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: /admin/games');
            exit;
        }
    }
    
    private function updateGame($data) {
        try {
            $stmt = $this->db->prepare("
                UPDATE dh_games 
                SET name = ?,
                    description = ?,
                    credits = ?,
                    voice_credits = ?,
                    is_active = ?,
                    display_order = ?,
                    route = ?,
                    image_path = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $data['name'],
                $data['description'],
                $data['credits'],
                $data['voice_credits'] ?? 0,
                isset($data['is_active']) ? 1 : 0,
                $data['display_order'],
                $data['route'],
                $data['image_path'],
                $data['id']
            ]);
            
            // Cache'i temizle
            $this->clearGameCache();
            
        } catch (Exception $e) {
            throw new Exception('Oyun güncellenirken bir hata oluştu: ' . $e->getMessage());
        }
    }
    
    // Cache temizleme metodu
    private function clearGameCache() {
        // Oyun ayarlarını kullanan tüm dosyaların cache'ini temizle
        clearstatcache(true);
    }
} 