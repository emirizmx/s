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
     * Loglama tabloları var mı kontrol eder ve yoksa oluşturur
     */
    public static function ensureLogTablesExist() {
        self::initDB();
        
        // Log tabloları var mı kontrolü
        $loginTable = self::$db->query("SHOW TABLES LIKE 'dh_login_logs'")->fetchAll();
        $systemTable = self::$db->query("SHOW TABLES LIKE 'dh_system_logs'")->fetchAll();
        
        if (count($loginTable) === 0 || count($systemTable) === 0) {
            // SQL dosyasından tabloları oluştur
            $sqlFile = __DIR__ . '/../sql/create_logs_tables.sql';
            if (file_exists($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                self::$db->exec($sql);
                return true;
            }
        }
        
        return true;
    }
    
    /**
     * Kullanıcı giriş logunu kaydeder
     * 
     * @param int|null $userId Kullanıcı ID (başarılı girişlerde)
     * @param string|null $username Kullanıcı adı
     * @param string|null $email Kullanıcı email
     * @param bool $success Giriş başarılı mı?
     * @param string|null $failReason Başarısız giriş nedeni
     * @return bool
     */
    public static function logLogin($userId = null, $username = null, $email = null, $success = true, $failReason = null) {
        self::initDB();
        
        try {
            $ipAddress = self::getClientIp();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $stmt = self::$db->prepare("
                INSERT INTO dh_login_logs 
                (user_id, username, email, ip_address, user_agent, status, fail_reason) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $userId,
                $username,
                $email,
                $ipAddress,
                $userAgent,
                $success ? 'success' : 'failed',
                $failReason
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log('Login log error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Sistem aktivite logunu kaydeder
     * 
     * @param string $action İşlem türü (ör: kredi_yükleme, oyun_başlatma)
     * @param string $module Modül adı (ör: kredi, oyun, kullanıcı)
     * @param string $description İşlem açıklaması
     * @param array|null $data İlave veriler (JSON olarak kaydedilir)
     * @param int|null $userId Kullanıcı ID (null ise mevcut oturumdan alınır)
     * @return bool
     */
    public static function logSystemActivity($action, $module, $description, $data = null, $userId = null) {
        self::initDB();
        
        try {
            if ($userId === null && isset($_SESSION['user_id'])) {
                $userId = $_SESSION['user_id'];
            }
            
            $ipAddress = self::getClientIp();
            $jsonData = $data ? json_encode($data, JSON_UNESCAPED_UNICODE) : null;
            
            $stmt = self::$db->prepare("
                INSERT INTO dh_system_logs 
                (user_id, action, module, description, data, ip_address) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $userId,
                $action,
                $module,
                $description,
                $jsonData,
                $ipAddress
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log('System log error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * İstemci IP adresini alır
     * 
     * @return string
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
} 