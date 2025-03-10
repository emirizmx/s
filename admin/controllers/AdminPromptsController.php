<?php
class AdminPromptsController {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function index() {
        try {
            // Promptları veritabanından al
            $stmt = $this->db->query("
                SELECT * FROM dh_prompts 
                ORDER BY name ASC
            ");
            $prompts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $pageTitle = 'Gemini Promptları';
            require 'views/prompts/index.php';
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Promptlar listelenirken bir hata oluştu: ' . $e->getMessage();
            header('Location: /admin');
            exit;
        }
    }
    
    public function edit() {
        try {
            $id = $_GET['id'] ?? null;
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->updatePrompt($_POST);
                $_SESSION['success'] = 'Prompt başarıyla güncellendi';
                header('Location: /admin/prompts');
                exit;
            }
            
            $stmt = $this->db->prepare("SELECT * FROM dh_prompts WHERE id = ?");
            $stmt->execute([$id]);
            $prompt = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$prompt) {
                throw new Exception('Prompt bulunamadı');
            }
            
            $pageTitle = 'Prompt Düzenle: ' . $prompt['name'];
            require 'views/prompts/edit.php';
            
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: /admin/prompts');
            exit;
        }
    }
    
    private function updatePrompt($data) {
        try {
            $stmt = $this->db->prepare("
                UPDATE dh_prompts 
                SET name = ?,
                    content = ?,
                    description = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $stmt->execute([
                $data['name'],
                $data['content'],
                $data['description'],
                $data['id']
            ]);
            
        } catch (Exception $e) {
            throw new Exception('Prompt güncellenirken bir hata oluştu: ' . $e->getMessage());
        }
    }
} 