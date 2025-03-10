<?php
require_once 'src/Models/Puzzle.php';

class GameController {
    private $db;
    private $puzzle;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->puzzle = new Puzzle($this->db);
    }
    
    public function play() {
        error_log('Puzzle GameController::play başladı');
        error_log('Current URL: ' . $_SERVER['REQUEST_URI']);
        error_log('Session durumu: ' . print_r($_SESSION, true));
        
        try {
            // Token kontrolü
            $token = $_GET['token'] ?? null;
            
            // URL'den token'ı al (eğer URL'de varsa)
            if (!$token && preg_match('/\/play\/([a-f0-9]{64})$/', $_SERVER['REQUEST_URI'], $matches)) {
                $token = $matches[1];
            }
            
            if (!$token) {
                $message = 'Geçersiz puzzle bağlantısı. Lütfen doğru bir bağlantı kullandığınızdan emin olun.';
                require 'src/Views/errors/invalid-puzzle.php';
                exit;
            }
            
            // Puzzle bilgilerini al
            $stmt = $this->db->prepare("
                SELECT p.*, u.username as creator_name, u.id as creator_id
                FROM puzzles p
                LEFT JOIN dh_users u ON p.user_id = u.id
                WHERE p.access_token = ?
            ");
            
            $stmt->execute([$token]);
            $puzzle = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$puzzle) {
                $message = 'Aradığınız puzzle bulunamadı veya artık mevcut değil.';
                require 'src/Views/errors/invalid-puzzle.php';
                exit;
            }
            
            // Erişim kontrolü
            if ($puzzle['visibility'] === 'private') {
                // Private puzzle için oturum ve sahiplik kontrolü
                if (!isset($_SESSION['user_id'])) {
                    $message = 'Bu puzzle\'ı görüntülemek için giriş yapmanız gerekiyor.';
                    require 'src/Views/errors/access-denied.php';
                    exit;
                }
                
                if ($_SESSION['user_id'] != $puzzle['creator_id']) {
                    $message = 'Bu puzzle özel olarak ayarlanmış ve sadece sahibi tarafından görüntülenebilir.';
                    require 'src/Views/errors/access-denied.php';
                    exit;
                }
            }
            
            // Kullanıcı bilgilerini al (eğer giriş yapılmışsa)
            $user = null;
            if (isset($_SESSION['user_id'])) {
                $stmt = $this->db->prepare("
                    SELECT username, credits 
                    FROM dh_users
                    WHERE id = ?
                ");
                
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            // Puzzle parçalarını hazırla
            $pieces = [];
            if (!$puzzle['is_completed']) {
                $pieces = $this->getPuzzlePieces($puzzle['id'], $puzzle['difficulty']);
            }
             // En iyi skoru al
             $stmt = $this->db->prepare("SELECT MIN(completion_time) as global_best_score FROM puzzle_scores WHERE puzzle_id = ?");
             $stmt->execute([$puzzle['id']]);
             $result = $stmt->fetch(PDO::FETCH_ASSOC);
             $bestScore = $result['global_best_score'];

            // Puzzle verilerini hazırla
            $puzzleData = [
                'id' => $puzzle['id'],
                'difficulty' => $puzzle['difficulty'],
                'gridSize' => $this->getGridSize($puzzle['difficulty']),
                'completionMessage' => $puzzle['completion_message'],
                'isCompleted' => (bool)$puzzle['is_completed'],
                'completionTime' => $puzzle['completion_time'] ?? null,
                'creator' => $puzzle['creator_name'],
                'visibility' => $puzzle['visibility'],
                'globalBestScore' => $bestScore
            ];
            
            // View'a verileri gönder
            require 'src/Views/game/play.php';
            
        } catch (Exception $e) {
            error_log('Puzzle GameController hatası: ' . $e->getMessage());
            
            if ($e->getMessage() === 'Puzzle bulunamadı' || $e->getMessage() === 'Geçersiz puzzle token') {
                $message = 'Aradığınız puzzle bulunamadı veya artık mevcut değil.';
                require 'src/Views/errors/invalid-puzzle.php';
                exit;
            }
            
            $_SESSION['error'] = $e->getMessage();
            header('Location: /puzzle');
            exit;
        }
    }

    private function getPuzzlePieces($puzzleId, $difficulty) {
        $gridSize = $this->getGridSize($difficulty);
        $pieces = [];
        
        for ($row = 0; $row < $gridSize; $row++) {
            for ($col = 0; $col < $gridSize; $col++) {
                $pieces[] = [
                    'id' => "{$row}_{$col}",
                    'path' => BASE_URL . "/uploads/pieces/{$puzzleId}/piece_{$row}_{$col}.png",
                    'correctPosition' => [
                        'row' => $row,
                        'col' => $col
                    ]
                ];
            }
        }
        
        // Parçaları karıştır
        shuffle($pieces);
        return $pieces;
    }

    private function getGridSize($difficulty) {
        switch($difficulty) {
            case 'easy':
                return 4;
            case 'medium':
                return 6;
            case 'hard':
                return 8;
            case 'insane':
                return 10;
            default:
                return 4;
        }
    }

    private function formatTime($seconds) {
        if (!$seconds) return '00:00';
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        return sprintf("%02d:%02d", $minutes, $remainingSeconds);
    }

    public function getTime() {
        try {
            if (!isset($_GET['puzzleId'])) {
                handleApiError('Puzzle ID gerekli', 400);
            }

            $puzzleId = $_GET['puzzleId'];
            $stmt = $this->db->prepare("
                SELECT elapsed_time 
                FROM puzzle_progress 
                WHERE puzzle_id = ? AND user_id = ?
            ");
            
            $stmt->execute([$puzzleId, $_SESSION['user_id']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'time' => $result ? (int)$result['elapsed_time'] : 0
            ]);
            
        } catch (Exception $e) {
            error_log('Get time error: ' . $e->getMessage());
            handleApiError('Süre alınamadı');
        }
    }

    public function saveTime() {
        header('Content-Type: application/json');
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['puzzle_id']) || !isset($data['time'])) {
                echo json_encode(['error' => 'Eksik parametreler']);
                return;
            }

            $stmt = $this->db->prepare("
                INSERT INTO puzzle_progress (puzzle_id, user_id, elapsed_time)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE elapsed_time = ?
            ");
            
            $stmt->execute([
                $data['puzzle_id'],
                $_SESSION['user_id'],
                $data['time'],
                $data['time']
            ]);
            
            echo json_encode(['success' => true]);
            
        } catch (Exception $e) {
            error_log('Save time error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Süre kaydedilemedi']);
        }
    }

    public function checkMoves() {
        header('Content-Type: application/json');
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['puzzle_id'])) {
                echo json_encode(['error' => 'Puzzle ID gerekli']);
                return;
            }

            $stmt = $this->db->prepare("
                SELECT moves 
                FROM puzzle_progress 
                WHERE puzzle_id = ? AND user_id = ?
            ");
            
            $stmt->execute([$data['puzzle_id'], $_SESSION['user_id']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'moves' => $result ? json_decode($result['moves'], true) : []
            ]);
            
        } catch (Exception $e) {
            error_log('Check moves error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Hamle kontrolü yapılamadı']);
        }
    }

    public function getPuzzleState() {
        try {
            if (!isset($_GET['puzzleId'])) {
                handleApiError('Puzzle ID gerekli', 400);
            }

            $puzzleId = $_GET['puzzleId'];
            
            $stmt = $this->db->prepare("
                SELECT p.*, pp.elapsed_time, pp.moves,
                       (SELECT MIN(completion_time) FROM puzzle_scores WHERE puzzle_id = p.id) as best_time
                FROM puzzles p
                LEFT JOIN puzzle_progress pp ON p.id = pp.puzzle_id AND pp.user_id = ?
                WHERE p.id = ?
            ");
            
            $stmt->execute([$_SESSION['user_id'], $puzzleId]);
            $puzzle = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$puzzle) {
                handleApiError('Puzzle bulunamadı', 404);
            }

            echo json_encode([
                'success' => true,
                'data' => [
                    'elapsed_time' => (int)($puzzle['elapsed_time'] ?? 0),
                    'moves' => json_decode($puzzle['moves'] ?? '[]'),
                    'is_completed' => (bool)$puzzle['is_completed'],
                    'best_time' => $puzzle['best_time'] ? (int)$puzzle['best_time'] : null
                ]
            ]);

        } catch (Exception $e) {
            error_log('Get puzzle state error: ' . $e->getMessage());
            handleApiError('Puzzle durumu alınamadı');
        }
    }
} 