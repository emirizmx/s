<?php
class Story {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function create($data) {
        try {
            $this->db->beginTransaction();
            
            // Share token'ı hikaye oluşturulurken üret
            $shareToken = $this->generateShareToken();
            
            $stmt = $this->db->prepare("
                INSERT INTO dh_stories (
                    user_id,
                    title,
                    content,
                    type,
                    narrative_style,
                    characters,
                    metadata,
                    share_token,
                    is_public,
                    created_at
                ) VALUES (
                    :user_id,
                    :title,
                    :content,
                    :type,
                    :narrative_style,
                    :characters,
                    :metadata,
                    :share_token,
                    :is_public,
                    NOW()
                )
            ");
            
            $stmt->execute([
                ':user_id' => $data['user_id'],
                ':title' => $data['title'],
                ':content' => $data['content'],
                ':type' => $data['type'],
                ':narrative_style' => $data['narrative_style'],
                ':characters' => json_encode($data['characters']),
                ':metadata' => json_encode($data['metadata']),
                ':share_token' => $shareToken,
                ':is_public' => true  // Varsayılan olarak herkese açık olarak değiştirildi
            ]);
            
            $storyId = $this->db->lastInsertId();
            $this->db->commit();
            
            return $storyId;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Story Creation Error: ' . $e->getMessage());
            throw new Exception('Hikaye oluşturulurken bir hata oluştu');
        }
    }
    
    public function addCharacter($storyId, $character) {
        try {
            error_log('Adding character: ' . print_r($character, true));
            
            $stmt = $this->db->prepare("
                INSERT INTO dh_story_characters 
                (story_id, name, type) 
                VALUES 
                (:story_id, :name, :type)
            ");
            
            $params = [
                ':story_id' => $storyId,
                ':name' => $character['name'],
                ':type' => $character['type']
            ];
            
            error_log('Executing query with params: ' . print_r($params, true));
            
            $stmt->execute($params);
            
        } catch (PDOException $e) {
            error_log('Add Character Error: ' . $e->getMessage());
            throw new Exception('Karakter eklenirken bir hata oluştu');
        }
    }
    
    public function get($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    s.*,
                    u.username as author,
                    CASE 
                        WHEN s.user_id = ? THEN true 
                        WHEN s.is_public = true THEN true 
                        ELSE false 
                    END as can_view
                FROM dh_stories s
                LEFT JOIN dh_users u ON s.user_id = u.id
                WHERE s.id = ?
            ");
            
            $stmt->execute([$_SESSION['user_id'] ?? 0, $id]);
            $story = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($story) {
                $story['characters'] = json_decode($story['characters'], true);
                $story['metadata'] = json_decode($story['metadata'], true);
            }
            
            return $story;
            
        } catch (PDOException $e) {
            error_log('Story Get Error: ' . $e->getMessage());
            throw new Exception('Hikaye yüklenirken bir hata oluştu');
        }
    }
    
    public function getUserStories($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    id,
                    title,
                    type,
                    narrative_style,
                    created_at,
                    audio_path,
                    metadata,
                    share_token,
                    is_public
                FROM dh_stories 
                WHERE user_id = ?
                ORDER BY created_at DESC
            ");
            
            $stmt->execute([$userId]);
            $stories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Her hikaye için metadata'yı decode et
            foreach ($stories as &$story) {
                $story['metadata'] = json_decode($story['metadata'], true);
            }
            
            return $stories;
            
        } catch (PDOException $e) {
            error_log('Get User Stories Error: ' . $e->getMessage());
            throw new Exception('Hikayeler yüklenirken bir hata oluştu');
        }
    }
    
    public function update($id, $data) {
        try {
            $updates = [];
            $params = ['id' => $id];
            
            foreach ($data as $key => $value) {
                $updates[] = "$key = :$key";
                $params[$key] = $value;
            }
            
            $sql = "UPDATE dh_stories SET " . implode(', ', $updates) . " WHERE id = :id";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
            
        } catch (PDOException $e) {
            error_log('Story Update Error: ' . $e->getMessage());
            throw new Exception('Hikaye güncellenirken bir hata oluştu');
        }
    }
    
    public function generateShareToken() {
        return bin2hex(random_bytes(32)); // 64 karakterlik güvenli token
    }
    
    public function updateShareSettings($id, $isPublic) {
        try {
            $stmt = $this->db->prepare("
                UPDATE dh_stories 
                SET is_public = :is_public
                WHERE id = :id
            ");
            
            $stmt->execute([
                ':id' => $id,
                ':is_public' => $isPublic
            ]);
            
            return true;
            
        } catch (PDOException $e) {
            error_log('Share Settings Update Error: ' . $e->getMessage());
            throw new Exception('Paylaşım ayarları güncellenirken bir hata oluştu');
        }
    }
    
    public function getByShareToken($token) {
        try {
            $stmt = $this->db->prepare("
                SELECT s.*, u.username as author
                FROM dh_stories s
                LEFT JOIN dh_users u ON s.user_id = u.id
                WHERE s.share_token = ?
            ");
            
            $stmt->execute([$token]);
            $story = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Hikaye bulunamadıysa veya public değilse ve kullanıcı sahibi değilse erişimi engelle
            if (!$story) {
                return null;
            }
            
            // Hikaye public değilse ve giriş yapmış kullanıcı hikayenin sahibi değilse erişimi engelle
            if (!$story['is_public'] && 
                (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $story['user_id'])) {
                return null;
            }
            
            return $story;
            
        } catch (PDOException $e) {
            error_log('Get By Share Token Error: ' . $e->getMessage());
            throw new Exception('Hikaye yüklenirken bir hata oluştu');
        }
    }
}