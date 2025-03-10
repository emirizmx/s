<?php
class Puzzle {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function create($data) {
        try {
            // Transaction başlat
            $this->db->beginTransaction();
            
            // Kullanıcının kredisini kontrol et
            $stmt = $this->db->prepare("
                SELECT credits FROM dh_users WHERE id = :user_id
            ");
            $stmt->execute([':user_id' => $data['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || $user['credits'] < 200) {
                throw new Exception('Yetersiz kredi! Puzzle oluşturmak için 200 krediye ihtiyacınız var.');
            }
            
            // Krediyi düş
            $stmt = $this->db->prepare("
                UPDATE dh_users 
                SET credits = credits - 200 
                WHERE id = :user_id
            ");
            $stmt->execute([':user_id' => $data['user_id']]);
            
            // Puzzle'ı oluştur
            $stmt = $this->db->prepare("
                INSERT INTO puzzles (
                    user_id, 
                    created_by,
                    access_token,
                    image_path, 
                    difficulty, 
                    completion_message,
                    visibility,
                    youtube_url
                ) VALUES (
                    :user_id,
                    :created_by,
                    :access_token,
                    :image_path, 
                    :difficulty,
                    :completion_message,
                    :visibility,
                    :youtube_url
                )
            ");
            
            $stmt->execute([
                ':user_id' => $data['user_id'],
                ':created_by' => $data['user_id'],
                ':access_token' => $data['access_token'],
                ':image_path' => $data['image_path'],
                ':difficulty' => $data['difficulty'],
                ':completion_message' => $data['completion_message'],
                ':visibility' => $data['visibility'],
                ':youtube_url' => $data['youtube_url']
            ]);
            
            $puzzleId = $this->db->lastInsertId();
            
            $this->db->commit();
            return $puzzleId;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    public function getRecentPuzzles($userId, $limit = 6) {
        $stmt = $this->db->prepare("
            SELECT id, image_path, difficulty, completion_time, score, visibility, access_token
            FROM puzzles
            WHERE user_id = :user_id
            ORDER BY created_at DESC
            LIMIT :limit
        ");
        
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function getPuzzle($id, $userId) {
        $stmt = $this->db->prepare("
            SELECT *
            FROM puzzles
            WHERE id = :id AND user_id = :user_id
        ");
        
        $stmt->execute([
            ':id' => $id,
            ':user_id' => $userId
        ]);
        
        return $stmt->fetch();
    }
    
    public function updateVisibility($puzzleId, $userId, $visibility) {
        try {
            $stmt = $this->db->prepare("
                UPDATE puzzles 
                SET visibility = :visibility
                WHERE id = :id AND user_id = :user_id
            ");
            
            $result = $stmt->execute([
                ':visibility' => $visibility,
                ':id' => $puzzleId,
                ':user_id' => $userId
            ]);

            // Güncelleme başarılı mı kontrol et
            if ($result && $stmt->rowCount() > 0) {
                return true;
            }
            return false;
        } catch (PDOException $e) {
            error_log('Puzzle visibility update error: ' . $e->getMessage());
            return false;
        }
    }

    // Kredi sorgulama metodu
    public function getUserCredits($userId) {
        $stmt = $this->db->prepare("
            SELECT credits FROM dh_users WHERE id = :user_id
        ");
        $stmt->execute([':user_id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['credits'] : 0;
    }
} 