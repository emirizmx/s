<?php
class LogHelper {
    private static $db;
    
    /**
     * Veritabanı bağlantısını başlatır
     */
    private static function initDB() {
        if (!self::$db) {
            self::$db = Database::getInstance()->getConnection();
        }
    }
    
    /**
     * Kullanıcı girişlerini loglar
     */
    public static function logLogin($userId, $username, $email, $isSuccess, $failReason = null) {
        self::initDB();
        self::ensureLogTablesExist();
        
        try {
            $stmt = self::$db->prepare("
                INSERT INTO dh_login_logs 
                (user_id, username, email, ip_address, user_agent, status, fail_reason)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $userId,
                $username,
                $email,
                self::getClientIp(),
                $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                $isSuccess ? 'success' : 'failed',
                $failReason
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log('Login log error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Sistem aktivitelerini loglar
     */
    public static function logSystemActivity($action, $module, $description, $details = null) {
        self::initDB();
        self::ensureLogTablesExist();
        
        try {
            $stmt = self::$db->prepare("
                INSERT INTO dh_system_logs 
                (action_type, module, description, user_id, details, ip_address)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $userId = $_SESSION['user_id'] ?? null;
            $detailsJson = $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null;
            
            $stmt->execute([
                $action,
                $module,
                $description,
                $userId,
                $detailsJson,
                self::getClientIp()
            ]);
            
        } catch (Exception $e) {
            error_log('Sistem log hatası: ' . $e->getMessage());
        }
    }
    
    /**
     * Loglama tabloları var mı kontrol eder ve yoksa oluşturur
     */
    public static function ensureLogTablesExist() {
        self::initDB();
        
        // Log tabloları var mı kontrolü
        $loginTable = self::$db->query("SHOW TABLES LIKE 'dh_login_logs'")->fetchAll();
        $systemTable = self::$db->query("SHOW TABLES LIKE 'dh_system_logs'")->fetchAll();
        
        if (count($loginTable) === 0 || count($systemTable) === 0) {
            // Tabloları oluştur
            $sql = "
            -- Kullanıcı giriş logları tablosu
            CREATE TABLE IF NOT EXISTS `dh_login_logs` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `user_id` int(11) DEFAULT NULL,
              `username` varchar(100) DEFAULT NULL,
              `email` varchar(255) DEFAULT NULL,
              `ip_address` varchar(45) NOT NULL,
              `user_agent` text,
              `status` enum('success','failed') NOT NULL,
              `fail_reason` varchar(255) DEFAULT NULL,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `user_id` (`user_id`),
              KEY `created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            -- Sistem aktivite logları tablosu
            CREATE TABLE IF NOT EXISTS `dh_system_logs` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `user_id` int(11) DEFAULT NULL,
              `action` varchar(50) NOT NULL,
              `module` varchar(50) NOT NULL,
              `description` text NOT NULL,
              `data` text,
              `ip_address` varchar(45) NOT NULL,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `user_id` (`user_id`),
              KEY `action` (`action`),
              KEY `module` (`module`),
              KEY `created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ";
            
            self::$db->exec($sql);
        }
    }
    
    /**
     * İstemci IP adresini alır
     */
    private static function getClientIp() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
        return $ip;
    }
    
    /**
     * Kredi işlemlerini ayrıca loglar
     * 
     * @param int $userId Kullanıcı ID
     * @param int $amount Kredi miktarı (harcama için negatif, yükleme için pozitif)
     * @param string $transactionType İşlem türü (örn: 'hikaye', 'puzzle', 'kredi_yukleme')
     * @param string $description İşlem açıklaması
     * @param array $details Ek detaylar
     */
    public static function logCreditTransaction($userId, $amount, $transactionType, $description, $details = []) {
        self::initDB();
        self::ensureLogTablesExist();
        
        try {
            // Sistem aktivitesi olarak logla
            $actionType = $amount > 0 ? 'kredi_yukleme' : 'kredi_kullanimi';
            $module = 'kredi';
            
            $detailsJson = json_encode($details, JSON_UNESCAPED_UNICODE);
            
            $stmt = self::$db->prepare("
                INSERT INTO dh_system_logs 
                (action_type, module, description, user_id, details, ip_address)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $actionType,
                $module,
                $description,
                $userId,
                $detailsJson,
                self::getClientIp()
            ]);
            
        } catch (Exception $e) {
            error_log('Kredi işlemi loglama hatası: ' . $e->getMessage());
        }
    }
    
    /**
     * Genişletilmiş kredi işlemi loglama
     */
    public static function logDetailedCreditTransaction($userId, $amount, $type, $productType, $description, $transaction = null) {
        try {
            self::initDB();
            
            // Log tablosuna kaydet
            $stmt = self::$db->prepare("
                INSERT INTO dh_system_logs 
                (action_type, module, description, user_id, details, ip_address)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $transactionResult = $transaction ? 'Başarılı' : 'Başarısız';
            $actionType = $type == 'credit' ? 'kredi_yukleme' : 'kredi_kullanimi';
            
            $details = [
                'user_id' => $userId,
                'amount' => $amount,
                'type' => $type,
                'product_type' => $productType,
                'transaction_id' => $transaction ? $transaction : null,
                'status' => $transactionResult
            ];
            
            $detailsJson = json_encode($details, JSON_UNESCAPED_UNICODE);
            
            $stmt->execute([
                $actionType,
                'kredi',
                $description . ' [' . $transactionResult . ']',
                $userId,
                $detailsJson,
                self::getClientIp()
            ]);
            
            // Doğrudan PHP hata loguna da kaydet
            error_log(sprintf(
                "KREDİ İŞLEMİ: [%s] UserID: %d, Amount: %d, Type: %s, Product: %s, Result: %s",
                date('Y-m-d H:i:s'),
                $userId,
                $amount,
                $type,
                $productType,
                $transactionResult
            ));
            
            return true;
        } catch (Exception $e) {
            error_log('Kredi işlemi loglama hatası: ' . $e->getMessage());
            return false;
        }
    }
} 