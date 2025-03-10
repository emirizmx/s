<?php
require_once 'src/Controllers/BaseController.php';

class ProfileController extends BaseController {
    
    public function edit() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        // dh_users tablosundan kullanıcı bilgilerini al
        $stmt = $this->db->prepare("SELECT id, username, email, phone FROM dh_users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        require_once 'src/Views/profile/edit.php';
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /profile/edit');
            exit;
        }
        
        try {
            $userId = $_SESSION['user_id'];
            
            // Form verilerini al
            $username = $_POST['username'] ?? '';
            $email = $_POST['email'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            // Güncellenecek alanları takip et
            $updatedFields = [];
            
            // Mevcut kullanıcı verilerini al
            $stmt = $this->db->prepare("SELECT username, email, phone, password_hash FROM dh_users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                throw new Exception('Kullanıcı bulunamadı.');
            }
            
            // Sorgu ve parametreleri hazırla
            $sql = "UPDATE dh_users SET ";
            $params = [];
            
            // Kullanıcı adı kontrolü
            if (!empty($username) && $username !== $user['username']) {
                // Kullanıcı adı benzersiz mi kontrol et
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM dh_users WHERE username = ? AND id != ?");
                $stmt->execute([$username, $userId]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception('Bu kullanıcı adı zaten kullanılıyor.');
                }
                
                $sql .= "username = ?, ";
                $params[] = $username;
                $updatedFields['username'] = [$user['username'], $username];
            }
            
            // E-posta kontrolü
            if (!empty($email) && $email !== $user['email']) {
                // E-posta geçerli mi kontrol et
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Geçersiz e-posta adresi.');
                }
                
                // E-posta benzersiz mi kontrol et
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM dh_users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $userId]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception('Bu e-posta adresi zaten kullanılıyor.');
                }
                
                $sql .= "email = ?, ";
                $params[] = $email;
                $updatedFields['email'] = [$user['email'], $email];
            }
            
            // Telefon güncelleme
            if ($phone !== $user['phone']) {
                $sql .= "phone = ?, ";
                $params[] = $phone;
                $updatedFields['phone'] = [$user['phone'], $phone];
            }
            
            // Şifre güncellemesi
            if (!empty($newPassword)) {
                // Mevcut şifre doğru mu kontrol et
                if (!password_verify($currentPassword, $user['password_hash'])) {
                    throw new Exception('Mevcut şifreniz hatalı.');
                }
                
                // Yeni şifre doğrulama
                if ($newPassword !== $confirmPassword) {
                    throw new Exception('Yeni şifre ve onay şifresi eşleşmiyor.');
                }
                
                if (strlen($newPassword) < 6) {
                    throw new Exception('Şifre en az 6 karakter olmalıdır.');
                }
                
                $sql .= "password_hash = ?, ";
                $params[] = password_hash($newPassword, PASSWORD_DEFAULT);
                $updatedFields['password'] = ['***', '*** (değiştirildi)'];
            }
            
            // Hiçbir alan değişmemişse
            if (empty($params)) {
                $_SESSION['success'] = 'Herhangi bir değişiklik yapılmadı.';
                header('Location: /profile');
                exit;
            }
            
            // SQL sorgusunu tamamla
            $sql = rtrim($sql, ', ') . " WHERE id = ?";
            $params[] = $userId;
            
            // Sorguyu çalıştır
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            // Profil güncellemesini logla
            LogHelper::logSystemActivity(
                'profil_guncelleme', 
                'kullanici', 
                'Kullanıcı #' . $userId . ' kendi profilini güncelledi', 
                [
                    'user_id' => $userId,
                    'updated_fields' => $updatedFields
                ]
            );
            
            // Session verilerini güncelle
            if (isset($updatedFields['username'])) {
                $_SESSION['username'] = $username;
            }
            
            if (isset($updatedFields['email'])) {
                $_SESSION['email'] = $email;
            }

            $_SESSION['success'] = 'Profil bilgileriniz başarıyla güncellendi.';
            
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        header('Location: /profile');
        exit;
    }
} 