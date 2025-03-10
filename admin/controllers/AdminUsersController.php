<?php
class AdminUsersController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function index() {
        $search = $_GET['search'] ?? '';
        $page = max(1, intval($_GET['page'] ?? 1));
        $perPage = 20;
        
        try {
            // Arama ve sayfalama için WHERE koşulu
            $params = [];
            $where = "WHERE is_admin = 0";
            
            if ($search) {
                $where .= " AND (username LIKE ? OR email LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            // Toplam kayıt sayısı
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM dh_users " . $where);
            $stmt->execute($params);
            $totalUsers = $stmt->fetchColumn();
            $totalPages = ceil($totalUsers / $perPage);
            
            // Kullanıcıları listele
            $offset = ($page - 1) * $perPage;
            $stmt = $this->db->prepare("
                SELECT u.id, u.username, u.email, u.credits, 
                       u.is_admin, u.is_active, 
                       u.created_at as kayit_tarihi,
                       u.updated_at as son_giris,
                       (SELECT COUNT(*) FROM puzzles WHERE user_id = u.id) as total_puzzles_created
                FROM dh_users u 
                {$where}
                ORDER BY u.created_at DESC
                LIMIT ?, ?
            ");
            
            // Önce arama parametrelerini ekle
            foreach ($params as $i => $param) {
                $stmt->bindValue($i + 1, $param);
            }
            
            // Sonra LIMIT parametrelerini ekle
            $stmt->bindValue(count($params) + 1, $offset, PDO::PARAM_INT);
            $stmt->bindValue(count($params) + 2, $perPage, PDO::PARAM_INT);
            
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // View'a gönder
            $pageTitle = 'Kullanıcılar';
            require 'views/users/index.php';
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Kullanıcılar listelenirken bir hata oluştu: ' . $e->getMessage();
            header('Location: /admin');
            exit;
        }
    }
    
    public function edit() {
        $userId = $_GET['id'] ?? 0;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->updateUser($userId, $_POST);
                $_SESSION['success'] = 'Kullanıcı başarıyla güncellendi.';
                header('Location: /admin/users');
                exit;
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
            }
        }
        
        $user = $this->getUser($userId);
        if (!$user) {
            header('Location: /admin/users');
            exit;
        }
        
        $pageTitle = 'Kullanıcı Düzenle';
        require 'views/users/edit.php';
    }
    
    private function getUsers($search, $page, $perPage) {
        try {
            $offset = ($page - 1) * $perPage;
            $params = [];
            $where = "WHERE is_admin = 0";
            
            if ($search) {
                $where .= " AND (username LIKE ? OR email LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            $stmt = $this->db->prepare("
                SELECT id, username, email, credits, created_at, is_active
                FROM dh_users 
                $where
                ORDER BY created_at DESC
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
    
    private function getTotalUsers($search) {
        try {
            $params = [];
            $where = "WHERE is_admin = 0";
            
            if ($search) {
                $where .= " AND (username LIKE ? OR email LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM dh_users $where
            ");
            
            $stmt->execute($params);
            return $stmt->fetchColumn();
            
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return 0;
        }
    }
    
    private function getUser($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, username, email, credits, created_at, is_active
                FROM dh_users 
                WHERE id = ? AND is_admin = 0
            ");
            
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        }
    }
    
    private function updateUser($id, $data) {
        try {
            $this->db->beginTransaction();
            
            $originalData = $this->getUser($id);
            
            // Güncellenen alanları izlemek için
            $updatedFields = [];
            
            // Temel kullanıcı bilgilerini güncelle
            $sql = "UPDATE dh_users SET ";
            $params = [];
            
            if (isset($data['username']) && $data['username'] !== $originalData['username']) {
                $sql .= "username = ?, ";
                $params[] = $data['username'];
                $updatedFields['username'] = [$originalData['username'], $data['username']];
            }
            
            if (isset($data['email']) && $data['email'] !== $originalData['email']) {
                $sql .= "email = ?, ";
                $params[] = $data['email'];
                $updatedFields['email'] = [$originalData['email'], $data['email']];
            }
            
            if (isset($data['is_admin']) && $data['is_admin'] !== $originalData['is_admin']) {
                $sql .= "is_admin = ?, ";
                $params[] = $data['is_admin'] ? 1 : 0;
                $updatedFields['is_admin'] = [$originalData['is_admin'], $data['is_admin'] ? 1 : 0];
            }
            
            if (isset($data['is_active']) && $data['is_active'] !== $originalData['is_active']) {
                $sql .= "is_active = ?, ";
                $params[] = $data['is_active'] ? 1 : 0;
                $updatedFields['is_active'] = [$originalData['is_active'], $data['is_active'] ? 1 : 0];
            }
            
            // Şifre değişikliği var mı kontrol et
            if (!empty($data['password'])) {
                $sql .= "password_hash = ?, ";
                $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
                $updatedFields['password'] = ['***', '*** (değiştirildi)'];
            }
            
            // Hiçbir alan güncellenmemişse, güncelleme yapılmaz
            if (empty($params)) {
                $this->db->rollBack();
                return true;
            }
            
            // SQL sorgusunu tamamla
            $sql = rtrim($sql, ', ') . " WHERE id = ?";
            $params[] = $id;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            // Kullanıcı güncellemesini logla
            LogHelper::logSystemActivity(
                'kullanici_guncelleme', 
                'admin', 
                'Admin tarafından kullanıcı #' . $id . ' güncellendi', 
                [
                    'admin_id' => $_SESSION['user_id'],
                    'user_id' => $id,
                    'updated_fields' => $updatedFields
                ]
            );
            
            $this->db->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw new Exception('Kullanıcı güncellenirken bir hata oluştu.');
        }
    }
} 