<?php

class PaymentNotificationsController extends AdminBaseController {
    
    public function index() {
        // Tüm bildirimleri listele
        $stmt = $this->db->prepare("
            SELECT pn.*, u.username, ba.bank_name 
            FROM dh_payment_notifications pn
            JOIN dh_users u ON pn.user_id = u.id
            JOIN dh_bank_accounts ba ON pn.bank_account_id = ba.id
            ORDER BY pn.created_at DESC
        ");
        $stmt->execute();
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        require 'admin/views/payments/notifications.php';
    }
    
    public function approve($id) {
        try {
            $this->db->beginTransaction();
            
            // Bildirimi al
            $stmt = $this->db->prepare("
                SELECT * FROM dh_payment_notifications 
                WHERE id = ? AND status = 'pending'
            ");
            $stmt->execute([$id]);
            $notification = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$notification) {
                throw new Exception('Bildirim bulunamadı veya zaten işlenmiş');
            }
            
            // İşlemi güncelle
            $stmt = $this->db->prepare("
                UPDATE dh_transactions 
                SET status = 'approved' 
                WHERE transaction_id = ?
            ");
            $stmt->execute([$notification['transaction_id']]);
            
            // Bildirimi güncelle
            $stmt = $this->db->prepare("
                UPDATE dh_payment_notifications 
                SET status = 'approved' 
                WHERE id = ?
            ");
            $stmt->execute([$id]);
            
            // Kullanıcıya kredileri ekle
            $stmt = $this->db->prepare("
                UPDATE dh_users 
                SET credits = credits + (
                    SELECT amount FROM dh_transactions 
                    WHERE transaction_id = ?
                )
                WHERE id = ?
            ");
            $stmt->execute([$notification['transaction_id'], $notification['user_id']]);
            
            $this->db->commit();
            $_SESSION['success'] = 'Ödeme onaylandı ve krediler eklendi';
            
        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['error'] = $e->getMessage();
        }
        
        header('Location: /admin/payment-notifications');
        exit;
    }
    
    public function reject($id) {
        try {
            $this->db->beginTransaction();
            
            // Bildirimi güncelle
            $stmt = $this->db->prepare("
                UPDATE dh_payment_notifications 
                SET status = 'rejected' 
                WHERE id = ? AND status = 'pending'
            ");
            $stmt->execute([$id]);
            
            // İşlemi güncelle
            $stmt = $this->db->prepare("
                UPDATE dh_transactions 
                SET status = 'rejected' 
                WHERE transaction_id = (
                    SELECT transaction_id FROM dh_payment_notifications WHERE id = ?
                )
            ");
            $stmt->execute([$id]);
            
            $this->db->commit();
            $_SESSION['success'] = 'Ödeme reddedildi';
            
        } catch (Exception $e) {
            $this->db->rollBack();
            $_SESSION['error'] = $e->getMessage();
        }
        
        header('Location: /admin/payment-notifications');
        exit;
    }
} 