<?php
/**
 * Debug amaçlı loglama yardımcısı
 * 
 * Bu sınıf, kredi işlemleri ve diğer önemli sistemler için detaylı loglama sağlar
 */
class DebugLogger {
    private static $logFile = 'credit_transactions_debug.log';
    private static $enabled = true;
    
    /**
     * Loga mesaj yazar
     * 
     * @param string $message Log mesajı
     * @param array|object $data Log ile ilişkili veri
     * @param string $level Log seviyesi (INFO, WARNING, ERROR)
     */
    public static function log($message, $data = null, $level = 'INFO') {
        if (!self::$enabled) {
            return;
        }
        
        try {
            // İlk olarak normal PHP hata logunu kullanalım
            error_log('DEBUG: ' . $message . ' - ' . json_encode($data, JSON_UNESCAPED_UNICODE));
            
            // Sonra özel log dosyasına yazmayı deneyelim
            $logPath = $_SERVER['DOCUMENT_ROOT'] . '/logs/credit_transactions_debug.log';
            $logDir = dirname($logPath);
            
            // Log dizini yoksa oluştur
            if (!file_exists($logDir)) {
                mkdir($logDir, 0755, true);
            }
            
            $timestamp = date('Y-m-d H:i:s');
            $formattedData = $data ? print_r($data, true) : 'N/A';
            
            $caller = 'Unknown';
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            if (isset($trace[1]['file'])) {
                $caller = basename($trace[1]['file']) . ':' . $trace[1]['line'];
                if (isset($trace[1]['function'])) {
                    $caller .= ' (' . $trace[1]['function'] . ')';
                }
            }
            
            $logEntry = sprintf(
                "[%s] [%s] [%s] %s\nData: %s\n--------------------\n",
                $timestamp,
                $level,
                $caller,
                $message,
                $formattedData
            );
            
            file_put_contents($logPath, $logEntry, FILE_APPEND);
            
            // Yedek olarak sistem temp dizinine de yazalım
            $tempLog = sys_get_temp_dir() . '/dh_credit_debug.log';
            file_put_contents($tempLog, $logEntry, FILE_APPEND);
        }
        catch (Exception $e) {
            // Log yazma başarısız olursa PHP hata loguna yazalım
            error_log('LOG ERROR: Failed to write to log file: ' . $e->getMessage());
        }
    }
    
    /**
     * SQL hata ayıklama logu
     * 
     * @param string $query SQL sorgusu
     * @param array $params SQL parametreleri
     * @param string $action İşlem türü (INSERT, UPDATE, vb.)
     */
    public static function logSQL($query, $params = [], $action = 'QUERY') {
        self::log(
            "SQL $action", 
            [
                'query' => $query,
                'params' => $params
            ]
        );
    }
    
    /**
     * Exception logu
     * 
     * @param Exception $e Hata nesnesi
     * @param string $context Bağlam bilgisi
     */
    public static function logException($e, $context = '') {
        self::log(
            "Exception: " . $e->getMessage() . ($context ? " ($context)" : ""),
            [
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ],
            'ERROR'
        );
    }
    
    /**
     * Kredi işlemi loglama
     * 
     * @param array $data Kredi işlem verileri
     * @param string $source İşlemin kaynağı (story, puzzle, vb.)
     */
    public static function logCreditTransaction($data, $source) {
        self::log(
            "Kredi İşlemi ($source)", 
            $data
        );
    }
    
    /**
     * Tablo yapısını kontrol eder
     */
    public static function checkTableStructure($tableName, $db) {
        try {
            $stmt = $db->prepare("DESCRIBE " . $tableName);
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            self::log("Tablo yapısı: " . $tableName, $columns);
            
            // Örnek bir kayıt ekleme denemesi
            if ($tableName === 'dh_credit_transactions') {
                try {
                    // Tablo kayıt sayısı 
                    $countStmt = $db->query("SELECT COUNT(*) FROM $tableName");
                    $rowCount = $countStmt->fetchColumn();
                    self::log("Mevcut kayıt sayısı", ['count' => $rowCount]);
                    
                    // Manuel SQL sorgusu ile test kaydı
                    $testSQL = "
                        INSERT INTO dh_credit_transactions 
                        (user_id, amount, type, product_type, description, created_at) 
                        VALUES (1, 1, 'debit', 'test_logger', 'DebugLogger test kaydı', NOW())
                    ";
                    $testResult = $db->exec($testSQL);
                    self::log("Test kaydı ekleme sonucu", ['affected_rows' => $testResult]);
                    
                    // Mevcut veri yapısını kontrol et
                    $sampleStmt = $db->query("SELECT * FROM $tableName ORDER BY id DESC LIMIT 1");
                    $sampleRow = $sampleStmt->fetch(PDO::FETCH_ASSOC);
                    self::log("Örnek kayıt", $sampleRow);
                    
                } catch (Exception $e) {
                    self::logException($e, 'Test kaydı sırasında hata: ' . $e->getMessage());
                    
                    // SQL hata kodlarını kontrol et
                    if ($e instanceof PDOException) {
                        self::log("PDO SQL State", ['sqlstate' => $e->errorInfo[0]]);
                        self::log("Driver Error Code", ['code' => $e->errorInfo[1]]);
                        self::log("Driver Error Message", ['message' => $e->errorInfo[2]]);
                    }
                }
            }
            
        } catch (Exception $e) {
            self::logException($e, "Tablo yapısı kontrolünde hata: $tableName");
        }
    }
} 

