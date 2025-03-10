<?php
class AdminCreditsController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function packages() {
        try {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->handlePackageSubmit();
            }
            
            $packages = $this->getPackages();
            $pageTitle = 'Kredi Paketleri';
            require 'views/credits/packages.php';
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'İşlem sırasında bir hata oluştu: ' . $e->getMessage();
            header('Location: /admin/dashboard');
            exit;
        }
    }
    
    public function editPackage() {
        try {
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->updatePackage($_POST);
                $_SESSION['success'] = 'Kredi paketi başarıyla güncellendi';
                header('Location: /admin/credits/packages');
                exit;
            }
            
            if ($id > 0) {
                $package = $this->getPackageById($id);
                if (!$package) {
                    throw new Exception('Kredi paketi bulunamadı');
                }
            } else {
                $package = [
                    'id' => 0,
                    'name' => '',
                    'credits' => 0,
                    'price' => 0,
                    'discount_percentage' => 0,
                    'is_active' => 1
                ];
            }
            
            $pageTitle = $id > 0 ? 'Kredi Paketi Düzenle' : 'Yeni Kredi Paketi';
            require 'views/credits/edit_package.php';
            
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: /admin/credits/packages');
            exit;
        }
    }
    
    private function handlePackageSubmit() {
        try {
            $action = $_POST['action'] ?? '';
            
            switch ($action) {
                case 'create':
                    $this->createPackage($_POST);
                    $_SESSION['success'] = 'Kredi paketi başarıyla oluşturuldu';
                    break;
                case 'update':
                    $this->updatePackage($_POST);
                    $_SESSION['success'] = 'Kredi paketi başarıyla güncellendi';
                    break;
                case 'delete':
                    $this->deletePackage($_POST['id']);
                    $_SESSION['success'] = 'Kredi paketi başarıyla silindi';
                    break;
                default:
                    throw new Exception('Geçersiz işlem');
            }
            
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
    }
    
    private function getPackages() {
        $stmt = $this->db->query("SELECT * FROM dh_credit_packages ORDER BY credits ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getPackageById($id) {
        $stmt = $this->db->prepare("SELECT * FROM dh_credit_packages WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function createPackage($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO dh_credit_packages 
                (name, credits, price, discount_percentage, is_active) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['name'],
                $data['credits'],
                $data['price'],
                $data['discount_percentage'],
                isset($data['is_active']) ? 1 : 0
            ]);
            
            // Kredi paketi oluşturulurken bir hata oluştu:
            LogHelper::logSystemActivity(
                'kredi_paketi_olusturma', 
                'admin', 
                'Admin #' . $_SESSION['user_id'] . ' tarafından yeni kredi paketi oluşturuldu',
                [
                    'admin_id' => $_SESSION['user_id'],
                    'package_id' => $this->db->lastInsertId(),
                    'package_name' => $data['name'],
                    'credits' => $data['credits'],
                    'price' => $data['price']
                ]
            );
            
        } catch (Exception $e) {
            throw new Exception('Kredi paketi oluşturulurken bir hata oluştu: ' . $e->getMessage());
        }
    }
    
    private function updatePackage($data) {
        try {
            $stmt = $this->db->prepare("
                UPDATE dh_credit_packages 
                SET name = ?, 
                    credits = ?, 
                    price = ?, 
                    discount_percentage = ?, 
                    is_active = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $data['name'],
                $data['credits'],
                $data['price'],
                $data['discount_percentage'],
                isset($data['is_active']) ? 1 : 0,
                $data['id']
            ]);
            
            // Paket güncellemesini logla
            LogHelper::logSystemActivity(
                'kredi_paketi_guncelleme', 
                'admin', 
                'Admin #' . $_SESSION['user_id'] . ' tarafından kredi paketi #' . $data['id'] . ' güncellendi',
                [
                    'admin_id' => $_SESSION['user_id'],
                    'package_id' => $data['id'],
                    'updated_fields' => [
                        'name' => $data['name'],
                        'credits' => $data['credits'],
                        'price' => $data['price'],
                        'discount_percentage' => $data['discount_percentage'],
                        'is_active' => isset($data['is_active']) ? 1 : 0
                    ]
                ]
            );
            
        } catch (Exception $e) {
            throw new Exception('Kredi paketi güncellenirken bir hata oluştu: ' . $e->getMessage());
        }
    }
    
    private function deletePackage($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM dh_credit_packages WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('Kredi paketi bulunamadı veya silinemedi');
            }
            
        } catch (Exception $e) {
            throw new Exception('Kredi paketi silinirken bir hata oluştu: ' . $e->getMessage());
        }
    }
    
    public function transactions() {
        $search = $_GET['search'] ?? '';
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = 20;
        
        $transactions = $this->getTransactions($search, $page, $perPage);
        $totalTransactions = $this->getTotalTransactions($search);
        $totalPages = ceil($totalTransactions / $perPage);
        
        $pageTitle = 'İşlem Geçmişi';
        require 'views/credits/transactions.php';
    }
    
    private function getTransactions($search, $page, $perPage) {
        try {
            $offset = ($page - 1) * $perPage;
            $params = [];
            $where = "";
            
            if ($search) {
                $where = "WHERE u.username LIKE ? OR u.email LIKE ?";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            $stmt = $this->db->prepare("
                SELECT t.*, u.username, u.email
                FROM dh_transactions t
                JOIN dh_users u ON t.user_id = u.id
                $where
                ORDER BY t.created_at DESC
                LIMIT ? OFFSET ?
            ");
            
            $params[] = $perPage;
            $params[] = $offset;
            
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }
    
    private function getTotalTransactions($search) {
        try {
            $params = [];
            $where = "";
            
            if ($search) {
                $where = "WHERE u.username LIKE ? OR u.email LIKE ?";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            $stmt = $this->db->prepare("
                SELECT COUNT(*)
                FROM dh_transactions t
                JOIN dh_users u ON t.user_id = u.id
                $where
            ");
            
            $stmt->execute($params);
            return $stmt->fetchColumn();
            
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return 0;
        }
    }
    
    public function addCredits() {
        // Manuel kredi ekleme işlemi...
        
        // Kredi başarıyla eklendiğinde
        LogHelper::logSystemActivity(
            'admin_kredi_ekleme', 
            'kredi', 
            'Admin tarafından kullanıcı #' . $userId . ' hesabına ' . $amount . ' kredi eklendi', 
            [
                'admin_id' => $_SESSION['user_id'],
                'user_id' => $userId,
                'amount' => $amount,
                'reason' => $reason
            ]
        );
    }
} 