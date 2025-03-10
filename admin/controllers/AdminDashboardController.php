<?php
require_once __DIR__ . '/../helpers/PromptHelper.php';

class AdminDashboardController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        
        // Promptlar tablosunun varlığını kontrol et
        PromptHelper::ensurePromptsTableExists($this->db);
    }
    
    public function index() {
        // İstatistikleri getir
        $stats = $this->getStats();
        
        // Son işlemleri getir
        $recentTransactions = $this->getRecentTransactions();
        
        // Son kullanıcıları getir
        $recentUsers = $this->getRecentUsers();
        
        $pageTitle = 'Dashboard';
        require 'views/dashboard/index.php';
    }
    
    private function getStats() {
        try {
            // Toplam kullanıcı sayısı
            $userCount = $this->db->query("SELECT COUNT(*) FROM dh_users WHERE is_admin = 0")->fetchColumn();
            
            // Toplam işlem sayısı
            $transactionCount = $this->db->query("SELECT COUNT(*) FROM dh_transactions")->fetchColumn();
            
            // Toplam gelir
            $totalRevenue = $this->db->query("
                SELECT COALESCE(SUM(amount), 0) 
                FROM dh_transactions 
                WHERE status = 'completed'
            ")->fetchColumn();
            
            // Bugünkü gelir
            $todayRevenue = $this->db->query("
                SELECT COALESCE(SUM(amount), 0) 
                FROM dh_transactions 
                WHERE status = 'completed' 
                AND DATE(created_at) = CURDATE()
            ")->fetchColumn();
            
            return [
                'user_count' => $userCount,
                'transaction_count' => $transactionCount,
                'total_revenue' => $totalRevenue,
                'today_revenue' => $todayRevenue
            ];
            
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [
                'user_count' => 0,
                'transaction_count' => 0,
                'total_revenue' => 0,
                'today_revenue' => 0
            ];
        }
    }
    
    private function getRecentTransactions() {
        try {
            $stmt = $this->db->query("
                SELECT t.*, u.username 
                FROM dh_transactions t
                JOIN dh_users u ON t.user_id = u.id
                ORDER BY t.created_at DESC
                LIMIT 5
            ");
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }
    
    private function getRecentUsers() {
        try {
            $stmt = $this->db->query("
                SELECT id, username, email, credits, created_at
                FROM dh_users
                WHERE is_admin = 0
                ORDER BY created_at DESC
                LIMIT 5
            ");
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }
} 