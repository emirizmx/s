<?php
require_once 'src/Helpers/LogHelper.php';
require_once 'src/Helpers/DebugLogger.php';

class GameController extends BaseController {
    public function __construct() {
        parent::__construct();
        
        // Tablo yapısını kontrol et
        DebugLogger::checkTableStructure('dh_credit_transactions', $this->db);
    }
    
    public function checkCredits($userId, $gameType) {
        $stmt = $this->db->prepare("
            SELECT credits FROM dh_users WHERE id = :user_id
        ");
        $stmt->execute([':user_id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $requiredCredits = $this->getRequiredCredits($gameType);
        
        return [
            'success' => $user['credits'] >= $requiredCredits,
            'current_credits' => $user['credits'],
            'required_credits' => $requiredCredits
        ];
    }
    
    public function useCredits($userId, $gameType) {
        try {
            $this->db->beginTransaction();
            
            $requiredCredits = $this->getRequiredCredits($gameType);
            
            // Krediyi düş
            $stmt = $this->db->prepare("
                UPDATE dh_users 
                SET credits = credits - :credits 
                WHERE id = :user_id AND credits >= :credits
            ");
            
            $result = $stmt->execute([
                ':credits' => $requiredCredits,
                ':user_id' => $userId
            ]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('Yetersiz kredi');
            }
            
            // Kredi kullanım kaydı
            $stmt = $this->db->prepare("
                INSERT INTO dh_credit_usage (
                    user_id, game_type, credits_spent
                ) VALUES (
                    :user_id, :game_type, :credits
                )
            ");
            
            $stmt->execute([
                ':user_id' => $userId,
                ':game_type' => $gameType,
                ':credits' => $requiredCredits
            ]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    private function getRequiredCredits($gameType) {
        switch($gameType) {
            case 'puzzle':
                return 200;
            default:
                return 0;
        }
    }
    
    public function redirectToPuzzle() {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT is_active FROM dh_games WHERE route = 'puzzle'");
        $stmt->execute();
        $game = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$game || $game['is_active'] == 0) {
            // Oyun pasif ise
            require $_SERVER['DOCUMENT_ROOT'] . '/includes/inactive.php';
            exit;
        }
        
        error_log('Puzzle yönlendirme başladı');
        error_log('Session durumu: ' . print_r($_SESSION, true));
        
        if (!isset($_SESSION['user_id'])) {
            error_log('Oturum kontrolü başarısız');
            $_SESSION['error'] = 'Lütfen önce giriş yapın';
            header('Location: /login');
            exit;
        }
        
        try {
            // Session domain ayarını değiştir
            ini_set('session.cookie_domain', '.dijitalhediye.com');
            
            // Puzzle token'ı oluştur
            $token = bin2hex(random_bytes(32));
            error_log('Token oluşturuldu: ' . $token);
            
            // Token'ı veritabanına kaydet
            $stmt = $this->db->prepare("
                UPDATE dh_users 
                SET puzzle_token = ?, 
                    puzzle_token_expires = DATE_ADD(NOW(), INTERVAL 5 MINUTE),
                    last_session_data = ?
                WHERE id = ?
            ");
            
            // Session verilerini JSON olarak sakla
            $sessionData = json_encode([
                'user_id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'credits' => $_SESSION['credits'],
                'logged_in' => true
            ]);
            
            $stmt->execute([$token, $sessionData, $_SESSION['user_id']]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('Token oluşturulamadı');
            }
            
            error_log('Token kaydedildi, yönlendirme yapılıyor: ' . PUZZLE_URL . '?token=' . $token);
            
            // Puzzle sistemine yönlendir
            header('Location: /puzzle/');
            exit;
            
        } catch (Exception $e) {
            error_log('Puzzle yönlendirme hatası: ' . $e->getMessage());
            $_SESSION['error'] = 'Puzzle sistemine yönlendirme başarısız oldu. Lütfen tekrar deneyin.';
            header('Location: /');
            exit;
        }
    }
    
    public function startPuzzle() {
        try {
            // Kullanıcı kontrolü
            if (!isset($_SESSION['user_id'])) {
                header('Location: /login');
                exit;
            }
            
            $userId = $_SESSION['user_id'];
            error_log("[KREDİ-TEST] Puzzle başlatılıyor. UserID: $userId");
            
            // Kredi kontrolü
            $gameType = 'puzzle';
            $requiredCredits = $this->getRequiredCredits($gameType);
            
            error_log("[KREDİ-TEST] Gerekli kredi: $requiredCredits");
            
            $stmt = $this->db->prepare("SELECT credits FROM dh_users WHERE id = ?");
            $stmt->execute([$userId]);
            $userCredits = $stmt->fetchColumn();
            
            error_log("[KREDİ-TEST] Kullanıcı kredisi: $userCredits");
            
            if ($userCredits < $requiredCredits) {
                error_log("[KREDİ-TEST] Yetersiz kredi!");
                $_SESSION['error'] = 'Yeterli krediniz bulunmamaktadır.';
                header('Location: /');
                exit;
            }
            
            // BURAYA KADAR GELDİYSE KREDİ YETERLİ
            error_log("[KREDİ-TEST] Kredi yeterli, işlem başlıyor.");
            
            // Bu kısım çok önemli. İşlemleri ayrı ayrı yapıp her adımı loglayacağız
            
            // 1. Kredi düşme işlemi
            try {
                $db = Database::getInstance()->getConnection();
                error_log("[KREDİ-TEST] Veritabanı bağlantısı alındı.");
                
                $creditUpdateSQL = "UPDATE dh_users SET credits = credits - $requiredCredits WHERE id = $userId";
                $affectedRows = $db->exec($creditUpdateSQL);
                
                error_log("[KREDİ-TEST] Kredi düşme sonucu: $affectedRows satır etkilendi");
                
                if ($affectedRows === 0) {
                    error_log("[KREDİ-TEST] Kredi düşülemedi!");
                    throw new Exception("Kredi düşme işlemi başarısız oldu.");
                }
                
                // 2. Kredi işlem kaydı - Doğrudan SQL ile
                $now = date('Y-m-d H:i:s');
                $transactionSQL = "
                    INSERT INTO dh_credit_transactions 
                    (user_id, amount, type, product_type, description, created_at) 
                    VALUES ($userId, $requiredCredits, 'debit', 'puzzle', 'Puzzle oyunu için kredi harcaması', '$now')
                ";
                
                $insertResult = $db->exec($transactionSQL);
                $lastId = $db->lastInsertId();
                
                error_log("[KREDİ-TEST] Kredi işlem kaydı sonucu: $insertResult satır eklendi. ID: $lastId");
                
                // 3. İşlem başarılı log kaydı
                LogHelper::logSystemActivity(
                    'kredi_kullanimi', 
                    'puzzle', 
                    "Kullanıcı #$userId tarafından puzzle için $requiredCredits kredi harcandı", 
                    [
                        'user_id' => $userId,
                        'amount' => $requiredCredits,
                        'transaction_id' => $lastId
                    ]
                );
                
                error_log("[KREDİ-TEST] LogHelper::logSystemActivity başarıyla çalıştı");
                
            } catch (Exception $e) {
                error_log("[KREDİ-TEST] HATA: " . $e->getMessage());
                throw $e;
            }
            
            // Kalan işlemler (token oluşturma vb.)
            // ...
            
        } catch (Exception $e) {
            error_log("[KREDİ-TEST] Ana hata: " . $e->getMessage());
            $_SESSION['error'] = 'İşlem sırasında bir hata oluştu: ' . $e->getMessage();
            header('Location: /');
            exit;
        }
    }
} 