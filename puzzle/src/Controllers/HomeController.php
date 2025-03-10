<?php
require_once 'src/Models/Puzzle.php';

class HomeController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /puzzle/login');
            exit;
        }

        $puzzle = new Puzzle($this->db);
        
        $this->checkAndCreateMissingTokens();
        
        $recentPuzzles = $puzzle->getRecentPuzzles($_SESSION['user_id']);
        
        // Kredi bilgilerini al - Puzzle modeli üzerinden değil, direkt veritabanından alıyoruz
        $stmt = $this->db->prepare("
            SELECT credits FROM dh_users 
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $userCredits = $user['credits'] ?? 0;
        
        // Puzzle oyunu için gereken kredi miktarını al
        $stmt = $this->db->prepare("
            SELECT credits FROM dh_games
            WHERE route = 'puzzle'
        ");
        $stmt->execute();
        $game = $stmt->fetch(PDO::FETCH_ASSOC);
        $gameCredits = isset($game['credits']) ? (int)$game['credits'] : 1;
        
        // Debug bilgisi
        error_log("Game credits: " . print_r($game, true));
        error_log("Required credits: " . $gameCredits);
        
        require 'src/Views/home/index.php';
    }
    
    private function checkAndCreateMissingTokens() {
        $query = "UPDATE puzzles SET access_token = MD5(CONCAT(id, RAND(), NOW())) 
                  WHERE access_token IS NULL OR access_token = ''";
        $this->db->query($query);
    }
    
    // Zaman formatı için yardımcı fonksiyon
    private function formatTime($seconds) {
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        return sprintf("%02d:%02d", $minutes, $remainingSeconds);
    }
} 