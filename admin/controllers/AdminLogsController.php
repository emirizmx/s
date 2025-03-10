<?php
require_once __DIR__ . '/../helpers/LogHelper.php';

class AdminLogsController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        LogHelper::ensureLogTablesExist();
    }
    
    /**
     * Giriş loglarını listele
     */
    public function loginLogs() {
        try {
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $perPage = 50;
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? '';
            
            $logs = $this->getLoginLogs($search, $status, $page, $perPage);
            $totalLogs = $this->getTotalLoginLogs($search, $status);
            $totalPages = ceil($totalLogs / $perPage);
            
            $pageTitle = 'Giriş Logları';
            require 'views/logs/login_logs.php';
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Loglar listelenirken bir hata oluştu: ' . $e->getMessage();
            header('Location: /admin/dashboard');
            exit;
        }
    }
    
    /**
     * Sistem loglarını listele
     */
    public function systemLogs() {
        try {
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $perPage = 50;
            $search = $_GET['search'] ?? '';
            $module = $_GET['module'] ?? '';
            $action = $_GET['action'] ?? '';
            $dateFrom = $_GET['date_from'] ?? '';
            $dateTo = $_GET['date_to'] ?? '';
            
            $logs = $this->getSystemLogs($search, $module, $action, $dateFrom, $dateTo, $page, $perPage);
            $totalLogs = $this->getTotalSystemLogs($search, $module, $action, $dateFrom, $dateTo);
            $totalPages = ceil($totalLogs / $perPage);
            
            // Mevcut modül ve işlem listelerini al
            $modules = $this->getDistinctModules();
            $actions = $this->getDistinctActions();
            
            $pageTitle = 'Sistem Logları';
            require 'views/logs/system_logs.php';
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Loglar listelenirken bir hata oluştu: ' . $e->getMessage();
            header('Location: /admin/dashboard');
            exit;
        }
    }
    
    /**
     * Giriş log detayı
     */
    public function loginLogDetail() {
        try {
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            
            if ($id <= 0) {
                throw new Exception('Geçersiz log ID');
            }
            
            $log = $this->getLoginLogById($id);
            
            if (!$log) {
                throw new Exception('Log bulunamadı');
            }
            
            $pageTitle = 'Giriş Log Detayı';
            require 'views/logs/login_log_detail.php';
            
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: /admin/logs/login');
            exit;
        }
    }
    
    /**
     * Sistem log detayı
     */
    public function systemLogDetail() {
        try {
            $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            
            if ($id <= 0) {
                throw new Exception('Geçersiz log ID');
            }
            
            $log = $this->getSystemLogById($id);
            
            if (!$log) {
                throw new Exception('Log bulunamadı');
            }
            
            // JSON veriyi decode et
            if (!empty($log['data'])) {
                $log['data_decoded'] = json_decode($log['data'], true);
            }
            
            $pageTitle = 'Sistem Log Detayı';
            require 'views/logs/system_log_detail.php';
            
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: /admin/logs/system');
            exit;
        }
    }
    
    /**
     * Giriş loglarını al
     */
    private function getLoginLogs($search, $status, $page, $perPage) {
        try {
            $params = [];
            $where = [];
            
            if ($search) {
                $where[] = "(username LIKE ? OR email LIKE ? OR ip_address LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            if ($status) {
                $where[] = "status = ?";
                $params[] = $status;
            }
            
            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            
            // Limit ve offset değerlerini string olarak değil, doğrudan sayı olarak kullan
            $offset = ($page - 1) * $perPage;
            
            $sql = "SELECT * FROM dh_login_logs $whereClause ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log('Error fetching login logs: ' . $e->getMessage());
            throw new Exception('Login logları alınırken bir hata oluştu');
        }
    }
    
    /**
     * Toplam giriş logu sayısını al
     */
    private function getTotalLoginLogs($search, $status) {
        $params = [];
        $where = [];
        
        if (!empty($search)) {
            $where[] = "(username LIKE ? OR email LIKE ? OR ip_address LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if (!empty($status)) {
            $where[] = "status = ?";
            $params[] = $status;
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT COUNT(*) FROM dh_login_logs $whereClause";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchColumn();
    }
    
    /**
     * Sistem loglarını al
     */
    private function getSystemLogs($search, $module, $action, $dateFrom, $dateTo, $page, $perPage) {
        try {
            $params = [];
            $where = [];
            
            if ($search) {
                $where[] = "(description LIKE ? OR data LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            if ($module) {
                $where[] = "module = ?";
                $params[] = $module;
            }
            
            if ($action) {
                $where[] = "action = ?";
                $params[] = $action;
            }
            
            if ($dateFrom) {
                $where[] = "created_at >= ?";
                $params[] = $dateFrom . ' 00:00:00';
            }
            
            if ($dateTo) {
                $where[] = "created_at <= ?";
                $params[] = $dateTo . ' 23:59:59';
            }
            
            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            
            // Limit ve offset değerlerini string olarak değil, doğrudan sayı olarak kullan
            $offset = ($page - 1) * $perPage;
            
            $sql = "
                SELECT s.*, u.username 
                FROM dh_system_logs s
                LEFT JOIN dh_users u ON s.user_id = u.id
                $whereClause
                ORDER BY s.created_at DESC 
                LIMIT $perPage OFFSET $offset
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // JSON verileri çöz
            foreach ($logs as &$log) {
                if (!empty($log['data'])) {
                    $decoded = json_decode($log['data'], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $log['data_decoded'] = $decoded;
                    }
                }
            }
            
            return $logs;
            
        } catch (PDOException $e) {
            error_log('Error fetching system logs: ' . $e->getMessage());
            throw new Exception('Sistem logları alınırken bir hata oluştu');
        }
    }
    
    /**
     * Toplam sistem logu sayısını al
     */
    private function getTotalSystemLogs($search, $module, $action, $dateFrom, $dateTo) {
        $params = [];
        $where = [];
        
        if (!empty($search)) {
            $where[] = "(description LIKE ? OR data LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if (!empty($module)) {
            $where[] = "module = ?";
            $params[] = $module;
        }
        
        if (!empty($action)) {
            $where[] = "action = ?";
            $params[] = $action;
        }
        
        if (!empty($dateFrom)) {
            $where[] = "created_at >= ?";
            $params[] = $dateFrom . ' 00:00:00';
        }
        
        if (!empty($dateTo)) {
            $where[] = "created_at <= ?";
            $params[] = $dateTo . ' 23:59:59';
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT COUNT(*) FROM dh_system_logs $whereClause";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchColumn();
    }
    
    /**
     * ID'ye göre giriş logu al
     */
    private function getLoginLogById($id) {
        $stmt = $this->db->prepare("SELECT * FROM dh_login_logs WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * ID'ye göre sistem logu al
     */
    private function getSystemLogById($id) {
        $stmt = $this->db->prepare("
            SELECT s.*, u.username 
            FROM dh_system_logs s
            LEFT JOIN dh_users u ON s.user_id = u.id
            WHERE s.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Benzersiz modülleri al
     */
    private function getDistinctModules() {
        $stmt = $this->db->query("SELECT DISTINCT module FROM dh_system_logs ORDER BY module");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Benzersiz işlemleri al
     */
    private function getDistinctActions() {
        $stmt = $this->db->query("SELECT DISTINCT action FROM dh_system_logs ORDER BY action");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
} 