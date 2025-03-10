<?php
require_once __DIR__ . '/../../../src/Controllers/BaseController.php';
require_once __DIR__ . '/../../../src/Helpers/LogHelper.php';
require_once __DIR__ . '/../Models/Story.php';
require_once __DIR__ . '/../Services/GeminiService.php';
require_once __DIR__ . '/../Services/TextToSpeechService.php';
require_once __DIR__ . '/../Models/Voice.php';
require_once __DIR__ . '/../../../src/Helpers/DebugLogger.php';

class StoryController extends BaseController {
    private $story;
    private $gemini;
    private $tts;
    private $isPublicAccess;
    
    public function __construct($isPublicAccess = false) {
        if (!$isPublicAccess) {
            parent::__construct();
        } else {
            // Public access için sadece DB bağlantısı kur
            global $db;
            $this->db = $db;
        }
        
        $this->isPublicAccess = $isPublicAccess;
        $this->story = new Story($this->db);
        $this->gemini = new GeminiService();
        $this->tts = new TextToSpeechService();
        
        // Oyun aktiflik kontrolü
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT is_active FROM dh_games WHERE route = 'story/list'");
        $stmt->execute();
        $game = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$game || $game['is_active'] == 0) {
            require $_SERVER['DOCUMENT_ROOT'] . '/includes/inactive.php';
            exit;
        }
    }
    
    public function index() {
        require __DIR__ . '/../Views/story/index.php';
    }
    
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $requiredCredits = $this->checkCredits('create');
                
                $title = $_POST['title'];
                $storyType = $_POST['storyType'];
                $narrativeStyle = $_POST['narrativeStyle'];
                $creationMethod = $_POST['creationMethod'];
                $characters = $_POST['characters'] ?? [];
                
                // Hikaye içeriğini oluştur
                $content = '';
                
                switch ($creationMethod) {
                    case 'manual':
                        // Kullanıcının kendi yazdığı hikaye
                        $content = $_POST['content'];
                        break;
                        
                    case 'hybrid':
                        // Karma mod - kullanıcı ana hatları veriyor, AI detaylandırıyor
                        $content = $this->generateHybridStory($_POST);
                        break;
                        
                    case 'ai':
                        // Tamamen AI tarafından oluşturulan hikaye
                        $content = $this->generateAIStory($_POST);
                        break;
                }
                
                // Hikayeyi kaydet
                $storyId = $this->story->create([
                    'user_id' => $_SESSION['user_id'],
                    'title' => $title,
                    'content' => $content,
                    'type' => $storyType,
                    'narrative_style' => $narrativeStyle,
                    'characters' => json_encode($characters),
                    'metadata' => json_encode([
                        'setting' => $_POST['setting'] ?? '',
                        'theme' => $_POST['theme'] ?? '',
                        'details' => $_POST['details'] ?? '',
                        'audience' => $_POST['audience'] ?? '',
                        'length' => $_POST['length'] ?? ''
                    ])
                ]);
                
                // Kredileri düş
                $this->useCredits($requiredCredits);
                
                echo json_encode(['success' => true, 'story_id' => $storyId]);
                
            } catch (Exception $e) {
                error_log('Story Creation Error: ' . $e->getMessage());
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;
        }
        
        require __DIR__ . '/../Views/story/create.php';
    }
    
    private function generateAIStory($data) {
        $prompt = $this->buildStoryPrompt($data);
        return $this->gemini->generateStory($prompt);
    }
    
    private function generateHybridStory($data) {
        $prompt = $this->buildHybridPrompt($data);
        return $this->gemini->generateStory($prompt);
    }
    
    private function buildStoryPrompt($data) {
        $storyTypes = [
            'fairytale' => 'masal',
            'love' => 'aşk hikayesi',
            'adventure' => 'macera hikayesi',
            'memory' => 'anı',
            'fantasy' => 'fantastik hikaye',
            'comedy' => 'komedi hikayesi'
        ];
        
        $narrativeStyles = [
            'classic' => 'klasik anlatım',
            'first_person' => 'birinci tekil şahıs',
            'third_person' => 'üçüncü tekil şahıs',
            'letter' => 'mektup formatı',
            'diary' => 'günlük formatı'
        ];
        
        $characters = array_map(function($char) {
            return $char['name'] . ' (' . $char['type'] . ')';
        }, $data['characters']);
        
        $prompt = "Bir {$storyTypes[$data['storyType']]} yaz. ";
        $prompt .= "Anlatım tarzı {$narrativeStyles[$data['narrativeStyle']]} olsun. ";
        
        if (!empty($data['setting'])) {
            $prompt .= "Hikaye {$data['setting']} geçiyor. ";
        }
        
        if (!empty($data['theme'])) {
            $prompt .= "Ana tema: {$data['theme']}. ";
        }
        
        $prompt .= "Karakterler: " . implode(', ', $characters) . ". ";
        
        if (!empty($data['details'])) {
            $prompt .= "Özel detaylar: {$data['details']}. ";
        }
        
        $prompt .= "Hedef kitle: {$data['audience']}. ";
        $prompt .= "Hikaye uzunluğu: {$data['length']}. ";
        
        return $prompt;
    }
    
    private function buildHybridPrompt($data) {
        $prompt = $this->buildStoryPrompt($data);
        $prompt .= "\n\nKullanıcının verdiği ana hatlar:\n{$data['details']}\n\n";
        $prompt .= "Bu ana hatları kullanarak detaylı ve sürükleyici bir hikaye oluştur.";
        return $prompt;
    }
    
    public function generateAudio($id) {
        try {
            error_log("generateAudio başladı - ID: " . $id);
            
            // POST verilerini kontrol edelim
            $rawInput = file_get_contents('php://input');
            error_log("Raw POST data: " . $rawInput);

            $postData = json_decode($rawInput, true);
            error_log("Decoded POST data: " . print_r($postData, true));

            // TTS sınıfını kontrol edelim
            if (!isset($this->tts)) {
                error_log("TTS sınıfı bulunamadı");
                throw new Exception('TTS sistemi başlatılamadı');
            }

            // Yetki kontrolü
            $story = $this->story->get($id);
            if (!$story) {
                error_log("Hikaye bulunamadı - ID: " . $id);
                throw new Exception('Hikaye bulunamadı');
            }

            if ($story['user_id'] !== $_SESSION['user_id']) {
                error_log("Yetkisiz erişim - User ID: " . $_SESSION['user_id']);
                throw new Exception('Yetkisiz erişim');
            }

            // Kredi kontrolü
            $requiredCredits = $this->checkCredits('voice');

            // POST verilerini kontrol et
            if (!$postData || !isset($postData['voiceId'])) {
                error_log("Geçersiz POST verisi");
                throw new Exception('Geçersiz istek formatı');
            }

            // Her adımı loglayalım
            try {
                error_log("ElevenLabs API çağrısı başlıyor - Voice ID: " . $postData['voiceId']);
                
                // Ses dosyasını oluştur ve Backblaze'e yükle
                $audioUrl = $this->tts->generateAudio($story['content'], $postData['voiceId']);
                
                if (!$audioUrl) {
                    error_log("Ses URL'i alınamadı");
                    throw new Exception('Ses dosyası oluşturulamadı');
                }

                error_log("Ses URL güncelleniyor: " . $audioUrl);
                
                // TextToSpeechService'den gelen URL'i direkt kullan
                $this->story->update($id, ['audio_path' => $audioUrl]);
                
                error_log("Kredi düşülüyor");
                
                $this->useCredits($requiredCredits);

                error_log("İşlem başarılı, yanıt gönderiliyor");
                
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'audio_url' => $audioUrl
                ]);

            } catch (Exception $e) {
                error_log("İç işlem hatası: " . $e->getMessage());
                throw new Exception('Ses dosyası oluşturulurken bir hata oluştu');
            }

        } catch (Exception $e) {
            error_log("generateAudio Error: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }
    
    public function list() {
        $stories = $this->story->getUserStories($_SESSION['user_id']);
        require __DIR__ . '/../Views/story/list.php';
    }
    
    public function view($id) {
        try {
            // Hikayeyi getir
            $story = $this->story->get($id);
            
            // Yetki kontrolü
            if (!$story || $story['user_id'] !== $_SESSION['user_id']) {
                throw new Exception('Yetkisiz erişim');
            }
            
            // Share token kontrolü ve oluşturma
            if (!isset($story['share_token']) || empty($story['share_token'])) {
                $shareToken = $this->story->generateShareToken();
                $this->story->update($id, ['share_token' => $shareToken]);
                $story['share_token'] = $shareToken;
            }
            
            // Share URL'i oluştur
            $story['share_url'] = 'https://dijitalhediye.com/story/share/' . $story['share_token'];
            
            require __DIR__ . '/../Views/story/view.php';
            
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            header('Location: /story/list');
            exit;
        }
    }
    
    private function checkCredits($action = 'create') {
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('Oturum süreniz dolmuş');
        }

        // Oyun ayarlarını veritabanından al
        $stmt = $this->db->prepare("
            SELECT credits, voice_credits 
            FROM dh_games 
            WHERE route = 'story/list'
        ");
        $stmt->execute();
        $game = $stmt->fetch(PDO::FETCH_ASSOC);

        // Kullanıcının mevcut kredilerini al
        $stmt = $this->db->prepare("
            SELECT credits 
            FROM dh_users 
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $requiredCredits = $action === 'create' ? $game['credits'] : $game['voice_credits'];

        if ($user['credits'] < $requiredCredits) {
            throw new Exception(
                $action === 'create' 
                ? 'Hikaye oluşturmak için yeterli krediniz yok. Gereken: ' . $requiredCredits . ' kredi' 
                : 'Ses oluşturmak için yeterli krediniz yok. Gereken: ' . $requiredCredits . ' kredi'
            );
        }

        return $requiredCredits;
    }
    
    private function useCredits($amount) {
        $stmt = $this->db->prepare("
            UPDATE dh_users 
            SET credits = credits - ? 
            WHERE id = ?
        ");
        $stmt->execute([$amount, $_SESSION['user_id']]);

        // Session'daki kredi miktarını güncelle
        $_SESSION['credits'] -= $amount;
    }
    
    public function share($id) {
        try {
            // Hata raporlamayı kapat
            error_reporting(0);
            ini_set('display_errors', 0);
            
            // Hikayeyi getir
            $story = $this->story->get($id);
            
            // Yetki kontrolü
            if (!$story || $story['user_id'] !== $_SESSION['user_id']) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Yetkisiz erişim']);
                exit;
            }
            
            // JSON verisini al
            $data = json_decode(file_get_contents('php://input'), true);
            $isPublic = isset($data['is_public']) ? (bool)$data['is_public'] : false;
            
            // Paylaşım ayarlarını güncelle
            $this->story->updateShareSettings($id, $isPublic);
            
            // Share token kontrolü
            if (!isset($story['share_token']) || empty($story['share_token'])) {
                $shareToken = $this->story->generateShareToken();
                $this->story->update($id, ['share_token' => $shareToken]);
                $story['share_token'] = $shareToken;
            }
            
            // Paylaşım URL'sini oluştur
            $shareUrl = $isPublic ? 
                'https://dijitalhediye.com/story/share/' . $story['share_token'] :
                null;
            
            // JSON yanıtı döndür
            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'share_url' => $shareUrl,
                'is_public' => $isPublic
            ], JSON_UNESCAPED_UNICODE);
            exit;
            
        } catch (Exception $e) {
            error_log('Share Error: ' . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    
    public function viewShared($token) {
        try {
            $story = $this->story->getByShareToken($token);
            
            if (!$story) {
                $error_message = 'Hikaye bulunamadı veya erişim kısıtlı';
                require __DIR__ . '/../Views/errors/error.php';
                exit;
            }
            
            if (!$story['is_public'] && 
                (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $story['user_id'])) {
                $error_message = 'Bu hikayeye erişim izniniz yok';
                require __DIR__ . '/../Views/errors/error.php';
                exit;
            }
            
            // Voice modelini kullan
            $voice = new Voice($this->db);
            $voices = $voice->getActiveVoices();
            
            // View'a voices değişkenini aktar
            require __DIR__ . '/../Views/story/shared.php';
            
        } catch (Exception $e) {
            $error_message = $e->getMessage();
            require __DIR__ . '/../Views/errors/error.php';
            exit;
        }
    }
    
    public function downloadAudio($id, $token = null) {
        try {
            ob_start();
            
            // Token varsa paylaşılan hikayeyi getir
            if ($token) {
                $story = $this->story->getByShareToken($token);
                // Hikaye public değilse ve kullanıcı giriş yapmamışsa erişimi engelle
                if (!$story['is_public'] && !isset($_SESSION['user_id'])) {
                    throw new Exception('Bu hikayeye erişim izniniz yok');
                }
            } else {
                // Normal hikaye indirme
                $story = $this->story->get($id);
                // Kullanıcı giriş yapmamışsa erişimi engelle
                if (!isset($_SESSION['user_id'])) {
                    throw new Exception('Bu hikayeye erişim izniniz yok');
                }
            }
            
            if (!$story || !$story['audio_path']) {
                throw new Exception('Ses dosyası bulunamadı');
            }

            // URL'den dosya adını al
            $fileName = basename($story['audio_path']);
            
            // İndirme adını ayarla
            $downloadName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $story['title']) . '.mp3';
            
            // Header'ları temizle ve yeniden ayarla
            ob_end_clean();
            header('Content-Type: audio/mpeg');
            header('Content-Disposition: attachment; filename="' . $downloadName . '"');
            header('Content-Transfer-Encoding: binary');
            header('Pragma: public');
            
            // Dosyayı oku ve gönder
            readfile($story['audio_path']);
            exit;
            
        } catch (Exception $e) {
            $error_message = $e->getMessage();
            require __DIR__ . '/../Views/errors/error.php';
            exit;
        }
    }
    
    // Story modelini güvenli bir şekilde erişmek için yeni metod
    public function getStory($id) {
        try {
            return $this->story->get($id);
        } catch (Exception $e) {
            error_log('Get Story Error: ' . $e->getMessage());
            return null;
        }
    }
    
    private function uploadToBackblaze($audioContent, $fileName) {
        try {
            // B2 config'i kontrol et
            if (!defined('B2_KEY_ID') || !defined('B2_APP_KEY') || !defined('B2_BUCKET_ID')) {
                throw new Exception('B2 yapılandırması eksik');
            }

            // B2 yetkilendirme
            $ch = curl_init('https://api.backblazeb2.com/b2api/v2/b2_authorize_account');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Basic ' . base64_encode(B2_KEY_ID . ':' . B2_APP_KEY)
                ]
            ]);
            
            $response = curl_exec($ch);
            
            if (curl_errno($ch)) {
                throw new Exception('B2 yetkilendirme hatası: ' . curl_error($ch));
            }
            
            $auth = json_decode($response, true);
            
            if (!isset($auth['authorizationToken']) || !isset($auth['apiUrl'])) {
                throw new Exception('Geçersiz B2 yetkilendirme yanıtı');
            }

            // Upload URL al
            $ch = curl_init($auth['apiUrl'] . '/b2api/v2/b2_get_upload_url');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode(['bucketId' => B2_BUCKET_ID]),
                CURLOPT_HTTPHEADER => [
                    'Authorization: ' . $auth['authorizationToken'],
                    'Content-Type: application/json'
                ]
            ]);
            
            $response = curl_exec($ch);
            
            if (curl_errno($ch)) {
                throw new Exception('B2 upload URL hatası: ' . curl_error($ch));
            }
            
            $uploadAuth = json_decode($response, true);
            
            if (!isset($uploadAuth['uploadUrl']) || !isset($uploadAuth['authorizationToken'])) {
                throw new Exception('Geçersiz B2 upload URL yanıtı');
            }

            // Dosyayı yükle
            $ch = curl_init($uploadAuth['uploadUrl']);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $audioContent,
                CURLOPT_HTTPHEADER => [
                    'Authorization: ' . $uploadAuth['authorizationToken'],
                    'X-Bz-File-Name: ' . 'audio/' . $fileName,
                    'Content-Type: audio/mpeg',
                    'Content-Length: ' . strlen($audioContent),
                    'X-Bz-Content-Sha1: ' . sha1($audioContent)
                ]
            ]);
            
            $response = curl_exec($ch);
            
            if (curl_errno($ch)) {
                throw new Exception('B2 yükleme hatası: ' . curl_error($ch));
            }
            
            $result = json_decode($response, true);
            
            if (!isset($result['fileId'])) {
                throw new Exception('Geçersiz B2 yükleme yanıtı');
            }

            // CDN URL'ini oluştur
            return B2_CDN_URL . '/audio/' . $fileName;

        } catch (Exception $e) {
            error_log('Backblaze Upload Error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function generateStory() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Geçersiz istek');
            }
            
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                throw new Exception('Oturum açmanız gerekiyor');
            }
            
            // Form verilerini al
            $prompt = $_POST['prompt'] ?? '';
            $name = $_POST['name'] ?? '';
            $ageGroup = $_POST['age_group'] ?? '';
            $theme = $_POST['theme'] ?? '';
            $voiceId = (int)($_POST['voice_id'] ?? 0);
            
            if (empty($prompt) || empty($name)) {
                throw new Exception('Lütfen tüm gerekli alanları doldurun');
            }
            
            // Kredi kontrolü
            $creditsNeeded = STORY_CREDITS;
            if ($voiceId > 0) {
                $creditsNeeded += VOICE_CREDITS;
            }
            
            $stmt = $this->db->prepare("SELECT credits FROM dh_users WHERE id = ?");
            $stmt->execute([$userId]);
            $userCredits = $stmt->fetchColumn();
            
            if ($userCredits < $creditsNeeded) {
                error_log("[STORY-KREDİ] Yetersiz kredi: $userCredits < $creditsNeeded");
                throw new Exception("Yeterli krediniz bulunmuyor. Gerekli kredi: $creditsNeeded");
            }
            
            // Hikaye oluşturma
            $storyText = $this->gemini->generateStory($prompt, $ageGroup, $theme);
            
            if (empty($storyText)) {
                throw new Exception('Hikaye oluşturulamadı. Lütfen tekrar deneyin.');
            }
            
            // Ses dosyası oluşturma
            $audioUrl = null;
            if ($voiceId > 0) {
                $ttsService = new TextToSpeechService();
                $audioUrl = $ttsService->convertToSpeech($storyText, $voiceId);
            }
            
            // Hikayeyi veritabanına kaydet
            $storyId = $this->story->saveStory($userId, $name, $prompt, $storyText, $audioUrl, $ageGroup, $theme, $voiceId);
            
            // Kredi işlemi
            $db = Database::getInstance()->getConnection();
            
            // 1. Krediyi düş
            $creditUpdateSQL = "UPDATE dh_users SET credits = credits - $creditsNeeded WHERE id = $userId";
            $affectedRows = $db->exec($creditUpdateSQL);
            
            error_log("[STORY-KREDİ] Kredi düşme sonucu: $affectedRows satır etkilendi");
            
            if ($affectedRows === 0) {
                error_log("[STORY-KREDİ] Kredi düşülemedi!");
                throw new Exception("Kredi düşürme işlemi başarısız.");
            }
            
            // 2. İşlem kaydı
            $productType = $voiceId > 0 ? 'story_voiceover' : 'story';
            $description = $voiceId > 0 ? 'Sesli hikaye oluşturma' : 'Hikaye oluşturma';
            $now = date('Y-m-d H:i:s');
            
            $transactionSQL = "
                INSERT INTO dh_credit_transactions 
                (user_id, amount, type, product_type, description, created_at) 
                VALUES ($userId, $creditsNeeded, 'debit', '$productType', '$description', '$now')
            ";
            
            $insertResult = $db->exec($transactionSQL);
            $lastId = $db->lastInsertId();
            
            error_log("[STORY-KREDİ] İşlem kaydı sonucu: $insertResult satır eklendi. ID: $lastId");
            
            // İşlem başarılı
            return [
                'success' => true,
                'story_id' => $storyId,
                'credits_used' => $creditsNeeded
            ];
            
        } catch (Exception $e) {
            error_log("[STORY-KREDİ] Hata: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
} 