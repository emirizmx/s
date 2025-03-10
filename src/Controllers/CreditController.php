<?php
require_once 'src/Models/Credit.php';
require_once 'src/Helpers/PayTR.php';

class CreditController extends BaseController {
    private $credit;
    private $paytr;
    protected $db;
    
    public function __construct() {
        parent::__construct();
        $this->credit = new Credit($this->db);
        $this->paytr = new PayTR();
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function index() {
        // Kredi paketlerini getir
        $packages = $this->getCreditPackages();
        
        // CSRF token oluştur
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        require 'src/Views/credits/index.php';
    }
    
    public function processPayment() {
        try {
            // Manuel tutar girişi kontrolü
            if (isset($_GET['amount'])) {
                $amount = floatval($_GET['amount']);
                
                // PayTR için merchant bilgileri
                $merchant = [
                    'merchant_id' => PAYTR_MERCHANT_ID,
                    'merchant_key' => PAYTR_MERCHANT_KEY,
                    'merchant_salt' => PAYTR_MERCHANT_SALT
                ];
                
                // Benzersiz sipariş ID oluştur
                $merchant_oid = time() . rand(1000, 9999);
                
                // Kullanıcı bilgileri
                $user = $this->getUserInfo($_SESSION['user_id']);
                
                // Ödeme bilgileri
                $payment = [
                    'amount' => $amount * 100,
                    'currency' => 'TL',
                    'user_ip' => $_SERVER['REMOTE_ADDR'],
                    'merchant_oid' => $merchant_oid,
                    'user_basket' => base64_encode(json_encode([
                        ['Manuel Kredi Yükleme', $amount, 1]
                    ])),
                    'debug_on' => 0,
                    'test_mode' => 1,
                    'no_installment' => 1,
                    'max_installment' => 0,
                    'user_name' => $user['username'],
                    'user_phone' => $user['phone'] ?? '',
                    'email' => $user['email'],
                    'user_email' => $user['email'],
                    'user_address' => 'Dijital Ürün',
                    'payment_amount' => $amount * 100
                ];
                
                // İşlemi veritabanına kaydet
                $stmt = $this->db->prepare("
                    INSERT INTO dh_transactions 
                    (user_id, amount, credits, payment_method, status, transaction_id) 
                    VALUES (?, ?, ?, 'credit_card', 'pending', ?)
                ");
                
                $stmt->execute([
                    $_SESSION['user_id'],
                    $amount,
                    $amount, // 1TL = 1 Kredi
                    $merchant_oid
                ]);

                // PayTR için callback URL'leri
                $urls = [
                    'merchant_ok_url' => SITE_URL . '/credits/success',
                    'merchant_fail_url' => SITE_URL . '/credits/failed',
                    'paytr_callback_url' => SITE_URL . '/credits/notify'
                ];
                
                // Token için string oluştur
                $hash_str = implode('', [
                    $merchant['merchant_id'],
                    $payment['user_ip'],
                    $payment['merchant_oid'],
                    $payment['email'],
                    $payment['payment_amount'],
                    $payment['user_basket'],
                    $payment['no_installment'],
                    $payment['max_installment'],
                    $payment['currency'],
                    $payment['test_mode']
                ]);
                
                // Token oluştur
                $paytr_token = base64_encode(hash_hmac('sha256', $hash_str . $merchant['merchant_salt'], $merchant['merchant_key'], true));
                
                // Form verilerini hazırla
                $post_vals = array_merge($merchant, $payment, $urls, ['paytr_token' => $paytr_token]);
                
                // Token al
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "https://www.paytr.com/odeme/api/get-token");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post_vals);
                curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 90);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 90);
                
                $result = curl_exec($ch);
                if (curl_errno($ch)) {
                    throw new Exception("PAYTR CURL Hatası: " . curl_error($ch));
                }
                curl_close($ch);
                
                $result = json_decode($result, true);
                
                if ($result['status'] === 'success') {
                    $token = $result['token'];
                    
                    // İframe sayfasını göster
                    echo '
                    <!DOCTYPE HTML>
                    <html lang="tr">
                    <head>
                        <meta charset="UTF-8">
                        <title>Ödeme Sayfası</title>
                        <style>
                            body, html {
                                margin: 0;
                                padding: 0;
                                width: 100%;
                                height: 100%;
                            }
                            iframe {
                                width: 100%;
                                height: 100%;
                                border: none;
                            }
                        </style>
                    </head>
                    <body>
                        <iframe src="https://www.paytr.com/odeme/guvenli/' . $token . '" frameborder="0" scrolling="no"></iframe>
                    </body>
                    </html>';
                    exit;
                } else {
                    throw new Exception("PAYTR Token Hatası: " . $result['reason']);
                }
                
            } else {
                // Paket seçimi ile ödeme için mevcut kod devam edecek...
                if (!isset($_POST['package_id'])) {
                    throw new Exception('Paket seçilmedi');
                }
                
                $packageId = $_POST['package_id'];
                $package = $this->getPackage($packageId);
                
                if (!$package) {
                    throw new Exception('Geçersiz paket');
                }
                
                // PayTR için merchant bilgileri
                $merchant = [
                    'merchant_id' => PAYTR_MERCHANT_ID,
                    'merchant_key' => PAYTR_MERCHANT_KEY,
                    'merchant_salt' => PAYTR_MERCHANT_SALT
                ];
                
                // Benzersiz sipariş ID oluştur
                $merchant_oid = time() . rand(1000, 9999);
                
                // Kullanıcı bilgileri
                $user = $this->getUserInfo($_SESSION['user_id']);
                
                // Ödeme bilgileri
                $payment = [
                    'amount' => $package['price'] * 100,
                    'currency' => 'TL',
                    'user_ip' => $_SERVER['REMOTE_ADDR'],
                    'merchant_oid' => $merchant_oid,
                    'user_basket' => base64_encode(json_encode([
                        [$package['name'], $package['price'], 1]
                    ])),
                    'debug_on' => 0,
                    'test_mode' => 1,
                    'no_installment' => 1,
                    'max_installment' => 0,
                    'user_name' => $user['username'],
                    'user_phone' => $user['phone'] ?? '',
                    'email' => $user['email'],
                    'user_email' => $user['email'],
                    'user_address' => 'Dijital Ürün',
                    'payment_amount' => $package['price'] * 100
                ];
                
                // İşlemi veritabanına kaydet
                $stmt = $this->db->prepare("
                    INSERT INTO dh_transactions 
                    (user_id, amount, credits, payment_method, status, transaction_id) 
                    VALUES (?, ?, ?, 'credit_card', 'pending', ?)
                ");
                
                $stmt->execute([
                $_SESSION['user_id'],
                    $package['price'],
                    $package['credits'],
                    $merchant_oid
                ]);
                
                // PayTR için callback URL'leri
                $urls = [
                    'merchant_ok_url' => SITE_URL . '/credits/success',
                    'merchant_fail_url' => SITE_URL . '/credits/failed',
                    'paytr_callback_url' => SITE_URL . '/credits/notify'
                ];
                
                // Token için string oluştur
                $hash_str = implode('', [
                    $merchant['merchant_id'],
                    $payment['user_ip'],
                    $payment['merchant_oid'],
                    $payment['email'],
                    $payment['payment_amount'],
                    $payment['user_basket'],
                    $payment['no_installment'],
                    $payment['max_installment'],
                    $payment['currency'],
                    $payment['test_mode']
                ]);
                
                // Token oluştur
                $paytr_token = base64_encode(hash_hmac('sha256', $hash_str . $merchant['merchant_salt'], $merchant['merchant_key'], true));
                
                // Form verilerini hazırla
                $post_vals = array_merge($merchant, $payment, $urls, ['paytr_token' => $paytr_token]);
                
                // Token al
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "https://www.paytr.com/odeme/api/get-token");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post_vals);
                curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 90);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 90);
                
                $result = curl_exec($ch);
                if (curl_errno($ch)) {
                    throw new Exception("PAYTR CURL Hatası: " . curl_error($ch));
                }
                curl_close($ch);
                
                $result = json_decode($result, true);
                
                if ($result['status'] === 'success') {
                    $token = $result['token'];
                    
                    // İframe sayfasını göster
                    echo '
                    <!DOCTYPE HTML>
                    <html lang="tr">
                    <head>
                        <meta charset="UTF-8">
                        <title>Ödeme Sayfası</title>
                        <style>
                            body, html {
                                margin: 0;
                                padding: 0;
                                width: 100%;
                                height: 100%;
                            }
                            iframe {
                                width: 100%;
                                height: 100%;
                                border: none;
                            }
                        </style>
                    </head>
                    <body>
                        <iframe src="https://www.paytr.com/odeme/guvenli/' . $token . '" frameborder="0" scrolling="no"></iframe>
                    </body>
                    </html>';
                    exit;
                } else {
                    throw new Exception("PAYTR Token Hatası: " . $result['reason']);
                }
            }
            
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: /credits');
            exit;
        }
    }

    public function callback() {
        try {
            $merchant_key = PAYTR_MERCHANT_KEY;
            $merchant_salt = PAYTR_MERCHANT_SALT;
            $hash = base64_encode(hash_hmac('sha256', $_POST['merchant_oid'] . $merchant_salt . $_POST['status'] . $_POST['total_amount'], $merchant_key, true));
            
            if ($hash != $_POST['hash']) {
                throw new Exception('PAYTR Hash doğrulama hatası');
            }
            
            $merchant_oid = $_POST['merchant_oid'];
            $status = $_POST['status'];
            
            $this->db->beginTransaction();
            
            // İşlem durumunu güncelle
            $stmt = $this->db->prepare("
                UPDATE dh_transactions 
                SET status = ?, 
                    updated_at = NOW() 
                WHERE transaction_id = ?
            ");
            
            $newStatus = ($status === 'success') ? 'approved' : 'rejected';
            $stmt->execute([$newStatus, $merchant_oid]);
            
            // Başarılı ödemede kredileri ekle
            if ($status === 'success') {
                // Önce işlem detaylarını al
                $stmt = $this->db->prepare("
                    SELECT t.user_id, t.credits, u.credits as current_credits 
                    FROM dh_transactions t
                    JOIN dh_users u ON t.user_id = u.id
                    WHERE t.transaction_id = ?
                ");
                $stmt->execute([$merchant_oid]);
                $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($transaction) {
                    // Kullanıcının kredilerini güncelle
                    $stmt = $this->db->prepare("
                        UPDATE dh_users 
                        SET credits = credits + ?,
                            updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$transaction['credits'], $transaction['user_id']]);
                    
                    // Session'daki kredi miktarını güncelle
                    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $transaction['user_id']) {
                        $_SESSION['credits'] = $transaction['current_credits'] + $transaction['credits'];
                    }
                }
            }
            
            $this->db->commit();
            echo "OK";
            
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('PayTR Callback Hatası: ' . $e->getMessage());
            echo "FAIL";
        }
    }
    
    public function paymentNotify() {
        try {
            // POST verisi kontrolü
            if (empty($_POST)) {
                throw new Exception('POST verisi boş');
            }

            // PayTR'den gelen verileri al
            $merchant_oid = $_POST['merchant_oid'] ?? null;
            $status = $_POST['status'] ?? null;
            $total_amount = $_POST['total_amount'] ?? null;
            $hash = $_POST['hash'] ?? null;

            if (!$merchant_oid || !$status || !$total_amount || !$hash) {
                throw new Exception('Eksik parametre');
            }

            // Hash doğrulama
            $hash_str = $merchant_oid . PAYTR_MERCHANT_SALT . $status . $total_amount;
            $hash2 = base64_encode(hash_hmac('sha256', $hash_str, PAYTR_MERCHANT_KEY, true));

            if ($hash != $hash2) {
                throw new Exception('Hash doğrulama hatası');
            }

            $this->db->beginTransaction();

            // İşlemi bul
            $stmt = $this->db->prepare("
                SELECT t.*, u.credits as current_credits 
                FROM dh_transactions t
                JOIN dh_users u ON t.user_id = u.id
                WHERE t.transaction_id = ? AND t.status = 'pending'
                LIMIT 1
            ");
            $stmt->execute([$merchant_oid]);
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$transaction) {
                throw new Exception('İşlem bulunamadı veya zaten tamamlanmış');
            }

            // İşlem durumunu güncelle
            $newStatus = ($status === 'success') ? 'approved' : 'failed';
            $stmt = $this->db->prepare("
                UPDATE dh_transactions 
                SET status = ?,
                    updated_at = NOW()
                WHERE transaction_id = ? AND status = 'pending'
            ");
            $stmt->execute([$newStatus, $merchant_oid]);

            // Başarılı ödemede kredileri ekle
            if ($status === 'success') {
                // Kullanıcının kredilerini güncelle
                $stmt = $this->db->prepare("
                    UPDATE dh_users 
                    SET credits = credits + ?
                    WHERE id = ?
                ");
                $stmt->execute([$transaction['credits'], $transaction['user_id']]);

                // Session'daki kredi miktarını güncelle (eğer aynı kullanıcı ise)
                if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $transaction['user_id']) {
                    $_SESSION['credits'] = $transaction['current_credits'] + $transaction['credits'];
                }

                error_log('Kredi yükleme başarılı: UserID=' . $transaction['user_id'] . 
                         ', Credits=' . $transaction['credits'] . 
                         ', TransactionID=' . $merchant_oid);
            } else {
                error_log('Ödeme başarısız: UserID=' . $transaction['user_id'] . 
                         ', TransactionID=' . $merchant_oid . 
                         ', Status=' . $status);
            }

            $this->db->commit();
            echo "OK";

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('PayTR Ödeme Hatası: ' . $e->getMessage() . 
                     ' - POST Data: ' . print_r($_POST, true));
            echo "FAIL";
        }
    }
    
    private function getCreditPackages() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM dh_credit_packages WHERE is_active = 1 ORDER BY credits ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }
    
    private function getPackage($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM dh_credit_packages WHERE id = ? AND is_active = 1");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        }
    }
    
    private function getUserInfo($userId) {
        try {
            $stmt = $this->db->prepare("SELECT username, email, phone FROM dh_users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || empty($user['email'])) {
                throw new Exception('Kullanıcı bilgileri bulunamadı veya email adresi eksik');
            }
            
            return $user;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            throw new Exception('Kullanıcı bilgileri alınırken bir hata oluştu');
        }
    }

    public function paymentSuccess() {
        $_SESSION['success'] = 'Ödeme işlemi başarıyla tamamlandı!';
        header('Location: /credits');
        exit;
    }

    public function paymentFailed() {
        $_SESSION['error'] = 'Ödeme işlemi başarısız oldu.';
        header('Location: /credits');
        exit;
    }

    public function checkPuzzleCredits($userId) {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        $stmt = $this->db->prepare("SELECT credits FROM dh_users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return ($user && $user['credits'] >= 200); // Puzzle başına 200 kredi
    }

    public function usePuzzleCredits($userId) {
        if (!$this->checkPuzzleCredits($userId)) {
            return false;
        }
        
        try {
            $this->db->beginTransaction();
            
            // Kredileri düş
            $stmt = $this->db->prepare("
                UPDATE dh_users 
                SET credits = credits - 200 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            
            // Kredi kullanım kaydı
            $stmt = $this->db->prepare("
                INSERT INTO dh_credit_usage (user_id, game_type, credits_spent)
                VALUES (?, 'puzzle', 200)
            ");
            $stmt->execute([$userId]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function manualProcess() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        try {
            // CSRF kontrolü
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                throw new Exception('Geçersiz işlem, lütfen tekrar deneyin.');
            }

            // Gerekli alanların kontrolü
            if (!isset($_POST['payment_method']) || !isset($_POST['credit_amount'])) {
                throw new Exception('Eksik bilgi gönderildi.');
            }

            $paymentMethod = $_POST['payment_method'];
            $creditAmount = (float)$_POST['credit_amount'];

            // Kredi kartı ödemesi için PayTR'ye yönlendir
            if ($paymentMethod === 'credit_card') {
                // Paket ID kontrolü
                if (isset($_POST['package_id'])) {
                    header('Location: /credits/process?package=' . $_POST['package_id']);
                } else {
                    // Manuel tutar girişi için
                    header('Location: /credits/process?amount=' . $creditAmount);
                }
                exit;
            }
            
            // Havale/EFT ödemesi için
            if ($paymentMethod === 'bank_transfer') {
                // Benzersiz transaction ID oluştur
                $transactionId = time() . rand(1000, 9999);
                
                // Ödeme kaydını oluştur
                $stmt = $this->db->prepare("
                    INSERT INTO dh_transactions (
                        user_id, 
                        amount, 
                        credits, 
                        payment_method, 
                        status,
                        transaction_id,
                        created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");

                $stmt->execute([
                    $_SESSION['user_id'],
                    $creditAmount,
                    $creditAmount, // 1TL = 1 Kredi
                    'bank_transfer',
                    'pending',
                    $transactionId
                ]);

                // Kullanıcıyı manuel ödeme sayfasına yönlendir
                header('Location: /credits/manual-purchase?transaction_id=' . $transactionId);
                exit;
            }

            throw new Exception('Geçersiz ödeme yöntemi.');

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: /credits');
            exit;
        }
    }

    public function manualPurchase() {
        try {
            // Transaction ID'yi al
            $transactionId = $_GET['transaction_id'] ?? null;
            if (!$transactionId) {
                throw new Exception('Geçersiz işlem');
            }

            // İşlemi kontrol et
            $stmt = $this->db->prepare("
                SELECT * FROM dh_transactions 
                WHERE transaction_id = ? AND user_id = ? AND status = 'pending'
            ");
            $stmt->execute([$transactionId, $_SESSION['user_id']]);
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$transaction) {
                throw new Exception('İşlem bulunamadı');
            }

            // Aktif banka hesaplarını getir
            $stmt = $this->db->prepare("SELECT * FROM dh_bank_accounts WHERE is_active = 1");
            $stmt->execute();
            $bankAccounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            require 'src/Views/credits/manual-purchase.php';

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: /credits');
            exit;
        }
    }

    public function notifyPayment() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Geçersiz istek');
            }

            // CSRF kontrolü
            if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
                throw new Exception('Güvenlik doğrulaması başarısız');
            }

            // Verileri al ve doğrula
            $transactionId = $_POST['transaction_id'] ?? null;
            $bankAccountId = $_POST['bank_account_id'] ?? null;
            $amount = floatval($_POST['amount'] ?? 0);
            $senderName = trim($_POST['sender_name'] ?? '');
            $transferDate = $_POST['transfer_date'] ?? '';
            $transferTime = $_POST['transfer_time'] ?? '';
            $referenceNumber = trim($_POST['reference_number'] ?? '');
            $note = trim($_POST['note'] ?? '');

            if (!$transactionId || !$bankAccountId || $amount <= 0 || !$senderName || !$transferDate || !$transferTime) {
                throw new Exception('Lütfen tüm zorunlu alanları doldurun');
            }

            // İşlemi kontrol et
            $stmt = $this->db->prepare("
                SELECT * FROM dh_transactions 
                WHERE transaction_id = ? AND user_id = ? AND status = 'pending'
            ");
            $stmt->execute([$transactionId, $_SESSION['user_id']]);
            $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$transaction) {
                throw new Exception('İşlem bulunamadı');
            }

            // Ödeme bildirimi oluştur
            $stmt = $this->db->prepare("
                INSERT INTO dh_payment_notifications 
                (transaction_id, user_id, bank_account_id, amount, sender_name, 
                 transfer_date, transfer_time, reference_number, note) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $transactionId,
                $_SESSION['user_id'],
                $bankAccountId,
                $amount,
                $senderName,
                $transferDate,
                $transferTime,
                $referenceNumber,
                $note
            ]);

            $_SESSION['success'] = 'Ödeme bildiriminiz alınmıştır. En kısa sürede kontrol edilecektir.';
            header('Location: /credits');
            exit;

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: /credits/manual-purchase?transaction_id=' . ($transactionId ?? ''));
            exit;
        }
    }

    // PayTR'den gelen ödeme bildirimi işleme
    public function notify() {
        try {
            $post = $_POST;
            
            // PayTR doğrulama kontrolü
            if (!$this->paytr->validateCallback($post)) {
                http_response_code(403);
                echo "PAYTR notification failed";
                exit;
            }
            
            $merchantOid = $post['merchant_oid'];
            $status = $post['status'];
            $totalAmount = $post['total_amount'];
            
            // İşlemi veritabanından al
            $transaction = $this->credit->getTransactionByReference($merchantOid);
            
            if (!$transaction) {
                throw new Exception("Transaction not found: " . $merchantOid);
            }
            
            // İşlem durumunu güncelle
            $this->credit->updateTransactionStatus(
                $transaction['id'], 
                $status === 'success' ? 'completed' : 'failed'
            );
            
            // Başarılı ödemede kredi ekle
            if ($status === 'success') {
                $this->credit->addCreditsFromTransaction($transaction['id']);
                
                // Kredi yükleme işlemini logla
                LogHelper::logSystemActivity(
                    'kredi_yukleme', 
                    'odeme', 
                    'Kullanıcı #' . $transaction['user_id'] . ' hesabına ' . $transaction['credits'] . ' kredi eklendi. Ödeme yöntemi: PayTR', 
                    [
                        'user_id' => $transaction['user_id'],
                        'transaction_id' => $transaction['id'],
                        'amount' => $transaction['amount'],
                        'credits' => $transaction['credits'],
                        'payment_method' => 'paytr'
                    ]
                );
            } else {
                // Başarısız ödeme logu
                LogHelper::logSystemActivity(
                    'odeme_hatasi', 
                    'odeme', 
                    'Kullanıcı #' . $transaction['user_id'] . ' için ödeme başarısız oldu',
                    [
                        'user_id' => $transaction['user_id'],
                        'transaction_id' => $transaction['id'],
                        'error' => $post['failed_reason_code'] ?? 'Bilinmeyen hata'
                    ]
                );
            }
            
            echo "OK";
            exit;
            
        } catch (Exception $e) {
            error_log($e->getMessage());
            http_response_code(500);
            echo "Error processing payment notification";
            exit;
        }
    }
    
    // Banka havalesi ile manuel kredi yükleme bildirimi
    public function submitManualPayment() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Geçersiz istek');
            }
            
            $userId = $_SESSION['user_id'];
            $bankAccountId = (int)$_POST['bank_account_id'];
            $packageId = (int)$_POST['package_id'];
            $amount = (float)$_POST['amount'];
            $senderName = $_POST['sender_name'];
            $referenceNumber = $_POST['reference_number'] ?? '';
            $note = $_POST['note'] ?? '';
            
            // Bilgileri doğrula
            if ($bankAccountId <= 0 || $packageId <= 0 || $amount <= 0 || empty($senderName)) {
                throw new Exception('Lütfen tüm bilgileri eksiksiz doldurun');
            }
            
            // Havale bildirimini veritabanına kaydet
            $stmt = $this->db->prepare("
                INSERT INTO dh_payment_notifications 
                (user_id, bank_account_id, package_id, amount, sender_name, reference_number, note, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            
            $stmt->execute([
                $userId, 
                $bankAccountId, 
                $packageId, 
                $amount, 
                $senderName, 
                $referenceNumber, 
                $note
            ]);
            
            // Havale bildirimini logla
            LogHelper::logSystemActivity(
                'havale_bildirimi', 
                'odeme', 
                'Kullanıcı #' . $userId . ' tarafından havale bildirimi yapıldı', 
                [
                    'user_id' => $userId,
                    'package_id' => $packageId,
                    'bank_account_id' => $bankAccountId,
                    'amount' => $amount,
                    'sender_name' => $senderName,
                    'reference_number' => $referenceNumber
                ]
            );

            $_SESSION['success'] = 'Ödeme bildiriminiz alınmıştır. En kısa sürede kontrol edilecektir.';
            header('Location: /credits');
            exit;

        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: /credits/manual-purchase?package_id=' . ($packageId ?? ''));
            exit;
        }
    }
} 