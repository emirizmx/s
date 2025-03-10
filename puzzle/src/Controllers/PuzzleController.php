<?php
require_once 'src/Models/Puzzle.php';

class PuzzleController {
    private $db;
    
    public function __construct() {
        try {
            // Veritabanı bağlantısını kontrol et
            error_log('=== PuzzleController Constructor Start ===');
            
            try {
                $database = Database::getInstance();
                $this->db = $database->getConnection();
                
                if (!$this->db) {
                    error_log('Database connection is null in constructor');
                    throw new Exception('Database connection failed');
                }
                
                error_log('Database connection established in constructor');
                
                // PDO hata modunu ayarla
                $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Test sorgusu
                $test = $this->db->query('SELECT 1')->fetch();
                error_log('Test query result: ' . print_r($test, true));
                
            } catch (Exception $e) {
                error_log('Constructor error: ' . $e->getMessage());
                error_log('Stack trace: ' . $e->getTraceAsString());
                throw $e;
            }
            
            // Oyun aktiflik kontrolü
            $stmt = $this->db->prepare("SELECT is_active FROM dh_games WHERE route = 'puzzle'");
            $stmt->execute();
            $game = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$game || $game['is_active'] == 0) {
                require $_SERVER['DOCUMENT_ROOT'] . '/includes/inactive.php';
                exit;
            }
            
            error_log('=== PuzzleController Constructor End ===');
        } catch (Exception $e) {
            error_log('Constructor error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }
    
    public function upload() {
        try {
            if (!isset($_SESSION['user_id'])) {
                throw new Exception('Oturum açmanız gerekiyor');
            }

            // Puzzle modelini oluştur
            $puzzle = new Puzzle($this->db);
            
            // Kullanıcının kredisini kontrol et
            $userCredits = $puzzle->getUserCredits($_SESSION['user_id']);
            if ($userCredits < 200) {
                throw new Exception('Yetersiz kredi! Puzzle oluşturmak için 200 krediye ihtiyacınız var.');
            }

            // Dosya kontrolü
            if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Lütfen bir resim seçin');
            }

            // Dosya boyutu kontrolü (2MB)
            if ($_FILES['image']['size'] > 2 * 1024 * 1024) {
                throw new Exception('Resim boyutu 2MB\'dan küçük olmalıdır');
            }

            // Dosya türü kontrolü
            $allowedTypes = ['image/jpeg', 'image/png'];
            if (!in_array($_FILES['image']['type'], $allowedTypes)) {
                throw new Exception('Sadece JPEG ve PNG formatları desteklenir');
            }

            // Zorluk seviyesi kontrolü
            if (!isset($_POST['difficulty']) || !in_array($_POST['difficulty'], ['easy', 'medium', 'hard', 'insane'])) {
                throw new Exception('Geçersiz zorluk seviyesi');
            }

            // Kırpma verilerini al
            $cropData = json_decode($_POST['cropData'], true);
            if (!$cropData) {
                throw new Exception('Lütfen puzzle için kullanılacak alanı seçin');
            }

            // Resmi yükle ve kırp
            $image = imagecreatefromstring(file_get_contents($_FILES['image']['tmp_name']));
            
            // Resmi döndür
            if ($cropData['rotate']) {
                $image = imagerotate($image, -$cropData['rotate'], 0);
            }
            
            // Kırpılmış resmi oluştur
            $croppedImage = imagecreatetruecolor($cropData['width'], $cropData['height']);
            imagecopy(
                $croppedImage, 
                $image, 
                0, 0, 
                $cropData['x'], $cropData['y'], 
                $cropData['width'], $cropData['height']
            );
            
            // Yeni resmi kaydet
            $uploadDir = __DIR__ . '/../../uploads/';
            $token = bin2hex(random_bytes(16));
            $fileName = $token . '.jpg';
            $uploadPath = '/uploads/' . $fileName;
            $fullPath = $uploadDir . $fileName;
            
            imagejpeg($croppedImage, $fullPath, 90);

            // Belleği temizle
            imagedestroy($image);
            imagedestroy($croppedImage);

            // Benzersiz token oluştur
            $accessToken = bin2hex(random_bytes(32)); // 64 karakterlik güvenli token

            // YouTube URL'ini işle
            $youtubeUrl = $_POST['youtube_url'] ?? '';
            $youtubeId = '';
            
            if (!empty($youtubeUrl)) {
                // YouTube video ID'sini çıkar
                if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $youtubeUrl, $matches)) {
                    $youtubeId = $matches[1];
                }
            }

            // Puzzle'ı oluştur ve krediyi düş
            $puzzleId = $puzzle->create([
                'user_id' => $_SESSION['user_id'],
                'image_path' => $uploadPath,
                'difficulty' => $_POST['difficulty'],
                'completion_message' => $_POST['completion_message'] ?? 'Tebrikler! Puzzle\'ı tamamladınız!',
                'visibility' => $_POST['visibility'] ?? 'public',
                'access_token' => $accessToken,
                'youtube_url' => $youtubeId
            ]);

            // Puzzle parçalarını oluştur
            $this->createPuzzlePieces($fullPath, $puzzleId, $_POST['difficulty']);

            // Başarılı sonuç
            $_SESSION['success'] = 'Puzzle başarıyla oluşturuldu! Erişim linki: ' . BASE_URL . '/play/' . $accessToken;
            header('Location: /puzzle/');
            exit;

        } catch (Exception $e) {
            // Hata durumunda yüklenen resmi sil
            if (isset($fullPath) && file_exists($fullPath)) {
                unlink($fullPath);
            }
            $_SESSION['error'] = $e->getMessage();
            header('Location: /puzzle/');
            exit;
        }
    }

    public function play() {
        $puzzleId = $_GET['id'] ?? 0;
        
        // Puzzle'ı ve erişim izinlerini kontrol et
        $query = "SELECT p.*, u.username 
                  FROM puzzles p 
                  LEFT JOIN users u ON p.created_by = u.id 
                  WHERE p.id = ?";
                  
        $stmt = $this->db->prepare($query);
        $stmt->execute([$puzzleId]);
        $puzzle = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$puzzle) {
            $_SESSION['error'] = 'Puzzle bulunamadı.';
            header('Location: /puzzle/');
            exit;
        }
        
        // Özel puzzle kontrolü - sadece oluşturan kullanıcı erişebilir
        if ($puzzle['visibility'] === 'private' && $puzzle['created_by'] !== $_SESSION['user_id']) {
            $_SESSION['error'] = 'Bu puzzle\'a erişim izniniz yok.';
            header('Location: /puzzle/');
            exit;
        }
        
        // Oyun verilerini hazırla...
        require_once 'src/Views/game/play.php';
    }

    private function createPuzzlePieces($imagePath, $puzzleId, $difficulty) {
        $gridSize = $this->getGridSize($difficulty);
        
        // Orijinal görüntüyü yükle
        $image = imagecreatefromstring(file_get_contents($imagePath));
        $width = imagesx($image);
        $height = imagesy($image);

        // Kare görüntü oluştur
        $size = min($width, $height);
        $squareImage = imagecreatetruecolor($size, $size);
        
        // Orijinal görüntüyü kare olarak kırp
        $srcX = ($width - $size) / 2;
        $srcY = ($height - $size) / 2;
        imagecopy($squareImage, $image, 0, 0, $srcX, $srcY, $size, $size);
        imagedestroy($image);

        // Parça boyutlarını hesapla
        $pieceSize = $size / $gridSize;

        $piecesDir = __DIR__ . '/../../uploads/pieces/' . $puzzleId;
        if (!file_exists($piecesDir)) {
            mkdir($piecesDir, 0777, true);
        }

        // Parçaları oluştur
        for ($row = 0; $row < $gridSize; $row++) {
            for ($col = 0; $col < $gridSize; $col++) {
                $piece = imagecreatetruecolor($pieceSize, $pieceSize);
                imagecopy(
                    $piece, 
                    $squareImage,
                    0, 0,
                    $col * $pieceSize, $row * $pieceSize,
                    $pieceSize, $pieceSize
                );

                $piecePath = $piecesDir . "/piece_{$row}_{$col}.png";
                imagepng($piece, $piecePath);
                imagedestroy($piece);
            }
        }

        imagedestroy($squareImage);
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

    private function checkCredits($userId) {
        try {
            // Oyun kredisini veritabanından al
            $stmt = $this->db->prepare("
                SELECT credits FROM dh_games 
                WHERE route = 'puzzle'
            ");
            $stmt->execute();
            $game = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Eğer oyun bulunamazsa varsayılan olarak 200 kredi kullan
            $requiredCredits = $game && isset($game['credits']) ? (int)$game['credits'] : 200;
            
            // Debug için log
            error_log("Puzzle için gerekli kredi: " . $requiredCredits);
            
            // Kullanıcının kredilerini kontrol et
            $stmt = $this->db->prepare("
                SELECT credits FROM dh_users 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                error_log("Kullanıcı bulunamadı: " . $userId);
                return false;
            }
            
            error_log("Kullanıcı kredisi: " . $user['credits'] . ", Gerekli kredi: " . $requiredCredits);
            
            // Yeterli kredi yoksa
            if ($user['credits'] < $requiredCredits) {
                error_log("Yetersiz kredi: " . $user['credits'] . " < " . $requiredCredits);
                return false;
            }
            
            // Kullanıcının kredisini düş
            $stmt = $this->db->prepare("
                UPDATE dh_users 
                SET credits = credits - ? 
                WHERE id = ?
            ");
            $stmt->execute([$requiredCredits, $userId]);
            
            // Kredi kullanım kayıtlarını tut
            $stmt = $this->db->prepare("
                INSERT INTO dh_credit_usage (user_id, game_type, amount) 
                VALUES (?, 'puzzle', ?)
            ");
            $stmt->execute([$userId, $requiredCredits]);
            
            return true;
        } catch (Exception $e) {
            error_log('Check credits error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function create() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        
        try {
            // Kredi kontrolü yap, false dönerse hata fırlat
            if (!$this->checkCredits($_SESSION['user_id'])) {
                throw new Exception('Yetersiz kredi! Puzzle oluşturmak için yeterli krediniz yok.');
            }
            
            // Mevcut puzzle oluşturma kodu...
            
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: /');
            exit;
        }
    }

    public function index() {
        try {
            // Önce basit bir sorgu deneyelim
            error_log('=== Simple Query Test ===');
            $test = $this->db->query('SELECT 1 as test')->fetch(PDO::FETCH_ASSOC);
            error_log('Test query result: ' . print_r($test, true));

            // Tabloları listele
            $tables = $this->db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            error_log('Available Tables: ' . implode(', ', $tables));

            // Puzzle oyun verilerini al
            $stmt = $this->db->prepare("
                SELECT * FROM dh_games 
                WHERE route = :route 
                LIMIT 1
            ");
            $stmt->execute(['route' => 'puzzle']);
            $game = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log('Puzzle game data: ' . ($game ? json_encode($game) : 'Not found'));

            if (!$game) {
                error_log('Game not found, checking if table exists...');
                
                // Tablo yoksa oluştur
                if (!in_array('dh_games', $tables)) {
                    error_log('Creating dh_games table...');
                    $this->createGamesTable();
                }
                
                // Varsayılan kaydı ekle
                error_log('Inserting default game record...');
                $stmt = $this->db->prepare("
                    INSERT INTO dh_games (
                        name, description, credits, is_active, route, image_path, display_order
                    ) VALUES (
                        'Romantik Puzzle',
                        'Sevdiğinize özel puzzle oluşturun',
                        21,
                        1,
                        'puzzle',
                        '/assets/images/puzzle.png',
                        1
                    )
                ");
                $result = $stmt->execute();
                error_log('Insert result: ' . ($result ? 'Success' : 'Failed'));
                
                // Yeni eklenen kaydı al
                $stmt = $this->db->prepare("SELECT * FROM dh_games WHERE route = :route");
                $stmt->execute(['route' => 'puzzle']);
                $game = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            // View'a gönderilecek verileri hazırla
            $viewData = [
                'game' => $game,
                'userCredits' => $_SESSION['credits'] ?? 0,
                'recentPuzzles' => $this->getRecentPuzzles()
            
            ];

            // View'a değişkenleri aktar
            extract($viewData);

            // View'ı yükle
            require __DIR__ . '/../../src/Views/home/index.php';

        } catch (Exception $e) {
            error_log('ERROR in index: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    private function createGamesTable() {
        $sql = "CREATE TABLE IF NOT EXISTS `dh_games` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `description` text,
            `credits` int(11) NOT NULL DEFAULT '200',
            `voice_credits` int(11) DEFAULT '0',
            `is_active` tinyint(1) NOT NULL DEFAULT '1',
            `route` varchar(255) NOT NULL,
            `image_path` varchar(255) NOT NULL,
            `display_order` int(11) NOT NULL DEFAULT '0',
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `route` (`route`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        $this->db->exec($sql);
    }
} 