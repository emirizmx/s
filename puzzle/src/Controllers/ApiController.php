<?php
require_once 'src/Models/Puzzle.php';  // Puzzle sınıfını dahil et

class ApiController {
    private $db;

    public function __construct() {
        // Veritabanı bağlantısını al
        require_once __DIR__ . '/../../config/database.php';
        global $db;
        $this->db = $db;
        
        // Bağlantı kontrolü
        if (!$this->db) {
            die("Veritabanı bağlantısı kurulamadı");
        }
    }

    public function checkMoves() {
        try {
            $puzzleId = $_GET['puzzleId'] ?? 0;
            $lastMoveId = $_GET['lastMoveId'] ?? 0;
            
            // Her parçanın en son durumunu al
            $query = "SELECT m1.* 
                     FROM puzzle_moves m1
                     INNER JOIN (
                         SELECT piece_id, MAX(id) as last_move_id
                         FROM puzzle_moves
                         WHERE puzzle_id = ?
                         GROUP BY piece_id
                     ) m2 ON m1.piece_id = m2.piece_id AND m1.id = m2.last_move_id
                     WHERE m1.puzzle_id = ? 
                     AND m1.id > ?
                     ORDER BY m1.id ASC";
                      
            $stmt = $this->db->prepare($query);
            $stmt->execute([$puzzleId, $puzzleId, $lastMoveId]);
            $moves = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json');
            echo json_encode(['moves' => $moves]);
        } catch (Exception $e) {
            header('HTTP/1.1 500 Internal Server Error');
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function saveMove() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['puzzleId']) || !isset($data['pieceId']) || !isset($data['slotId'])) {
                throw new Exception('Eksik veri');
            }
            
            // Önce bu parçanın önceki konumunu pasif yap
            $updateQuery = "UPDATE puzzle_moves 
                           SET is_active = 0 
                           WHERE puzzle_id = ? 
                           AND piece_id = ? 
                           AND is_active = 1";
                           
            $stmt = $this->db->prepare($updateQuery);
            $stmt->execute([
                $data['puzzleId'],
                $data['pieceId']
            ]);
            
            // Yeni konumu kaydet
            $insertQuery = "INSERT INTO puzzle_moves 
                           (puzzle_id, piece_id, slot_id, is_return, is_active) 
                           VALUES (?, ?, ?, ?, 1)";
                          
            $stmt = $this->db->prepare($insertQuery);
            $success = $stmt->execute([
                $data['puzzleId'],
                $data['pieceId'],
                $data['slotId'],
                isset($data['isReturn']) ? $data['isReturn'] : 0
            ]);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => $success]);
        } catch (Exception $e) {
            header('HTTP/1.1 500 Internal Server Error');
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function saveTime() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['puzzleId']) || !isset($data['seconds'])) {
                throw new Exception('Eksik veri');
            }
            
            // Önce puzzle'ın var olup olmadığını kontrol et
            $checkQuery = "SELECT id FROM puzzles WHERE id = ?";
            $stmt = $this->db->prepare($checkQuery);
            $stmt->execute([$data['puzzleId']]);
            
            if (!$stmt->fetch()) {
                throw new Exception('Puzzle bulunamadı');
            }
            
            $query = "UPDATE puzzles 
                      SET elapsed_time = ? 
                      WHERE id = ?";
                      
            $stmt = $this->db->prepare($query);
            $success = $stmt->execute([
                $data['seconds'],
                $data['puzzleId']
            ]);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => $success
            ]);
        } catch (Exception $e) {
            header('HTTP/1.1 500 Internal Server Error');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getTime() {
        try {
            $puzzleId = $_GET['puzzleId'] ?? 0;
            
            if (!$puzzleId) {
                throw new Exception('Geçersiz puzzle ID');
            }
            
            // Önce puzzle'ın var olup olmadığını kontrol et
            $checkQuery = "SELECT id FROM puzzles WHERE id = ?";
            $stmt = $this->db->prepare($checkQuery);
            $stmt->execute([$puzzleId]);
            
            if (!$stmt->fetch()) {
                throw new Exception('Puzzle bulunamadı');
            }
            
            $query = "SELECT COALESCE(elapsed_time, 0) as elapsed_time 
                      FROM puzzles 
                      WHERE id = ?";
                      
            $stmt = $this->db->prepare($query);
            $stmt->execute([$puzzleId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'seconds' => (int)($result['elapsed_time'] ?? 0)
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function togglePause() {
        $data = json_decode(file_get_contents('php://input'), true);
        $puzzleId = $data['puzzleId'] ?? 0;
        $isPaused = $data['isPaused'] ?? false;
        
        try {
            $query = "UPDATE puzzles SET is_paused = ? WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$isPaused ? 1 : 0, $puzzleId]);
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function resetPuzzle() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $puzzleId = $data['puzzleId'] ?? 0;
            
            // Transaction başlat
            $this->db->beginTransaction();
            
            // Puzzle'ı tamamen sıfırla
            $stmt = $this->db->prepare("
                UPDATE puzzles 
                SET elapsed_time = 0,
                    is_paused = 0,
                    is_completed = 0,
                    completion_time = NULL,
                    reset_timestamp = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$puzzleId]);
            
            // Hamleleri temizle
            $stmt = $this->db->prepare("DELETE FROM puzzle_moves WHERE puzzle_id = ?");
            $stmt->execute([$puzzleId]);
            
            // Transaction'ı tamamla
            $this->db->commit();
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            // Hata durumunda rollback yap
            $this->db->rollBack();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function getGridSize($puzzleId) {
        $query = "SELECT difficulty FROM puzzles WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$puzzleId]);
        $puzzle = $stmt->fetch(PDO::FETCH_ASSOC);
        
        switch($puzzle['difficulty']) {
            case 'easy': return 4;
            case 'medium': return 6;
            case 'hard': return 8;
            case 'insane': return 10;
            default: return 4;
        }
    }

    public function getPuzzleState() {
        try {
            $puzzleId = $_GET['puzzleId'] ?? 0;
            
            if (!$puzzleId) {
                throw new Exception('Geçersiz puzzle ID');
            }
            
            $query = "SELECT id, is_paused, completion_time, reset_timestamp, elapsed_time, completion_message, is_completed 
                      FROM puzzles 
                      WHERE id = ?";
                      
            $stmt = $this->db->prepare($query);
            $stmt->execute([$puzzleId]);
            $puzzle = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$puzzle) {
                throw new Exception('Puzzle bulunamadı');
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'id' => $puzzle['id'],
                'is_paused' => (int)$puzzle['is_paused'],
                'completion_time' => $puzzle['completion_time'],
                'reset_timestamp' => $puzzle['reset_timestamp'],
                'elapsed_time' => (int)$puzzle['elapsed_time'],
                'completion_message' => $puzzle['completion_message'],
                'is_completed' => (int)$puzzle['is_completed']
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function saveScore() {
        $data = json_decode(file_get_contents('php://input'), true);
        $puzzleId = $data['puzzleId'] ?? 0;
        $completionTime = $data['completionTime'] ?? 0;
        $userId = $_SESSION['user_id'] ?? 0;

        try {
            // Transaction başlat
            $this->db->beginTransaction();
            
            // Skoru kaydet
            $stmt = $this->db->prepare("INSERT INTO puzzle_scores (puzzle_id, user_id, completion_time) VALUES (?, ?, ?)");
            $stmt->execute([$puzzleId, $userId, $completionTime]);

            // Puzzle'ı tamamlandı olarak işaretle ve son süreyi kaydet
            $stmt = $this->db->prepare("UPDATE puzzles SET is_completed = 1, completion_time = ?, elapsed_time = ? WHERE id = ?");
            $stmt->execute([$completionTime, $completionTime, $puzzleId]);

            // En iyi skoru al
            $stmt = $this->db->prepare("SELECT MIN(completion_time) as global_best_score FROM puzzle_scores WHERE puzzle_id = ?");
            $stmt->execute([$puzzleId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $this->db->commit();

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'globalBestScore' => $result['global_best_score']
            ]);
        } catch (Exception $e) {
            $this->db->rollBack();
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getImagePath() {
        try {
            $puzzleId = $_GET['puzzleId'] ?? 0;
            
            $query = "SELECT image_path 
                      FROM puzzles 
                      WHERE id = ?";
                      
            $stmt = $this->db->prepare($query);
            $stmt->execute([$puzzleId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                throw new Exception('Puzzle bulunamadı');
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'image_path' => '/puzzle' . $result['image_path']
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function updatePuzzleVisibility() {
        try {
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('Oturum açmanız gerekiyor');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['puzzleId']) || !isset($data['visibility'])) {
                throw new Exception('Geçersiz istek parametreleri');
            }

            // Puzzle modelini oluştur
            $puzzle = new Puzzle($this->db);
            
            // Güncelleme işlemini yap
            $result = $puzzle->updateVisibility(
                $data['puzzleId'], 
                $_SESSION['user_id'], 
                $data['visibility']
            );

            if (!$result) {
                throw new Exception('Güncelleme başarısız oldu');
            }

            // Başarılı yanıt döndür
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Oyun durumu başarıyla güncellendi'
            ]);

        } catch (Exception $e) {
            // Hata durumunda
            error_log('Update visibility error: ' . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function deleteMoves() {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $puzzleId = $data['puzzleId'] ?? 0;
            
            $stmt = $this->db->prepare("DELETE FROM puzzle_moves WHERE puzzle_id = ?");
            $stmt->execute([$puzzleId]);
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function updateCompletionMessage() {
        try {
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('Oturum açmanız gerekiyor');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['puzzleId']) || !isset($data['message'])) {
                throw new Exception('Geçersiz istek parametreleri');
            }

            $stmt = $this->db->prepare("
                UPDATE puzzles 
                SET completion_message = :message
                WHERE id = :id AND user_id = :user_id
            ");
            
            $result = $stmt->execute([
                ':message' => $data['message'],
                ':id' => $data['puzzleId'],
                ':user_id' => $_SESSION['user_id']
            ]);

            if (!$result) {
                throw new Exception('Güncelleme başarısız oldu');
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Mesaj başarıyla güncellendi'
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function checkYoutubeUrl() {
        try {
            if (!isset($_GET['url'])) {
                throw new Exception('URL parametresi eksik');
            }

            $url = $_GET['url'];
            $videoId = '';

            // Video ID'sini çıkar - regex'i basitleştirelim
            if (preg_match('/[?&]v=([a-zA-Z0-9_-]{11})/', $url, $matches)) {
                $videoId = $matches[1];
            } else if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]{11})/', $url, $matches)) {
                $videoId = $matches[1];
            } else {
                throw new Exception('Geçersiz YouTube URL\'si');
            }

            // YouTube API'ye istek at
            $apiUrl = sprintf(
                'https://www.googleapis.com/youtube/v3/videos?part=snippet&id=%s&key=%s',
                $videoId,
                YOUTUBE_API_KEY
            );

            // cURL kullanarak istek yapalım
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                throw new Exception('cURL Error: ' . curl_error($ch));
            }
            
            curl_close($ch);

            if ($httpCode !== 200) {
                throw new Exception('YouTube API Hatası: HTTP ' . $httpCode);
            }

            $data = json_decode($response, true);
            if (!isset($data['items'][0])) {
                throw new Exception('Video bulunamadı');
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $data['items'][0]['snippet']
            ]);

        } catch (Exception $e) {
            error_log('YouTube API Error: ' . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
} 