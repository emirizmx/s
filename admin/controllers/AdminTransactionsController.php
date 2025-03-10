<?php
class AdminTransactionsController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function index() {
        $search = $_GET['search'] ?? '';
        
        try {
            // İşlemleri ve ödeme bildirimlerini getir
            $stmt = $this->db->prepare("
                SELECT 
                    t.*,
                    u.username,
                    u.email,
                    pn.id as notification_id,
                    pn.sender_name,
                    pn.transfer_date,
                    pn.reference_number,
                    ba.bank_name
                FROM dh_transactions t
                LEFT JOIN dh_users u ON t.user_id = u.id
                LEFT JOIN dh_payment_notifications pn ON t.transaction_id = pn.transaction_id
                LEFT JOIN dh_bank_accounts ba ON pn.bank_account_id = ba.id
                WHERE 1=1
                " . ($search ? "AND (t.transaction_id LIKE ? OR u.username LIKE ? OR u.email LIKE ?)" : "") . "
                ORDER BY t.created_at DESC
            ");

            if ($search) {
                $stmt->execute(["%$search%", "%$search%", "%$search%"]);
            } else {
                $stmt->execute();
            }
            
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // View'a gönder
            $pageTitle = 'Kredi İşlemleri';
            require 'views/credits/transactions.php';
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'İşlemler listelenirken bir hata oluştu: ' . $e->getMessage();
            header('Location: /admin');
            exit;
        }
    }
    
    public function updateStatus() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Geçersiz istek metodu');
            }

            $transactionId = $_POST['transaction_id'] ?? null;
            $status = $_POST['status'] ?? null;
            
            if (!$transactionId || !$status) {
                throw new Exception('Geçersiz parametreler');
            }

            $this->db->beginTransaction();

            // İşlem ve bildirim durumunu güncelle
            $stmt = $this->db->prepare("
                UPDATE dh_transactions t
                LEFT JOIN dh_payment_notifications pn ON t.transaction_id = pn.transaction_id
                SET t.status = ?, pn.status = ?
                WHERE t.id = ?
            ");
            $stmt->execute([$status, $status, $transactionId]);

            // Eğer onaylandıysa kullanıcıya kredileri ekle
            if ($status === 'approved') {
                $stmt = $this->db->prepare("
                    UPDATE dh_users u
                    JOIN dh_transactions t ON u.id = t.user_id
                    SET u.credits = u.credits + t.credits
                    WHERE t.id = ?
                ");
                $stmt->execute([$transactionId]);
            }

            $this->db->commit();
            $_SESSION['success'] = $status === 'approved' ? 'Ödeme onaylandı ve krediler eklendi' : 'Ödeme reddedildi';

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $_SESSION['error'] = 'İşlem güncellenirken bir hata oluştu: ' . $e->getMessage();
        }

        header('Location: /admin/credits/transactions');
        exit;
    }
} 