<?php
class TextToSpeechService {
    private $apiKey;
    private $apiUrl;
    private $b2AuthToken;
    private $b2ApiUrl;
    private $b2UploadUrl;
    private $b2UploadAuthToken;
    private static $isB2Authorized = false;
    
    public function __construct() {
        // Konfigürasyon dosyalarını yükle
        require_once __DIR__ . '/../../config/tts.php';
        require_once __DIR__ . '/../../config/b2.php';
        
        $this->apiKey = TTS_API_KEY;
        $this->apiUrl = TTS_API_URL;
        
        // B2 yetkilendirmesini sadece bir kez yap
        if (!self::$isB2Authorized) {
            $this->authorizeB2();
            self::$isB2Authorized = true;
        }
    }
    
    private function authorizeB2() {
        try {
            error_log('B2 yetkilendirme başlatılıyor...');
            
            // B2 kimlik bilgilerini kontrol et
            if (!defined('B2_KEY_ID') || !defined('B2_APP_KEY')) {
                throw new Exception('B2 kimlik bilgileri tanımlanmamış');
            }
            
            $ch = curl_init('https://api.backblazeb2.com/b2api/v2/b2_authorize_account');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Basic ' . base64_encode(B2_KEY_ID . ':' . B2_APP_KEY)
                ]
            ]);
            
            $response = curl_exec($ch);
            
            if (curl_errno($ch)) {
                throw new Exception('B2 API isteği başarısız: ' . curl_error($ch));
            }
            
            $result = json_decode($response, true);
            
            if (!isset($result['authorizationToken'])) {
                error_log('B2 API yanıtı: ' . $response);
                throw new Exception('B2 yetkilendirme hatası: Geçersiz yanıt');
            }
            
            $this->b2AuthToken = $result['authorizationToken'];
            $this->b2ApiUrl = $result['apiUrl'];
            
            // Upload URL al
            $ch = curl_init($this->b2ApiUrl . '/b2api/v2/b2_get_upload_url');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode(['bucketId' => B2_BUCKET_ID]),
                CURLOPT_HTTPHEADER => [
                    'Authorization: ' . $this->b2AuthToken,
                    'Content-Type: application/json'
                ]
            ]);
            
            $response = curl_exec($ch);
            
            if (curl_errno($ch)) {
                throw new Exception('B2 upload URL isteği başarısız: ' . curl_error($ch));
            }
            
            $result = json_decode($response, true);
            
            if (!isset($result['uploadUrl'])) {
                error_log('B2 Upload URL yanıtı: ' . $response);
                throw new Exception('B2 upload URL alınamadı: Geçersiz yanıt');
            }
            
            $this->b2UploadUrl = $result['uploadUrl'];
            $this->b2UploadAuthToken = $result['authorizationToken'];
            
            error_log('B2 yetkilendirme başarılı');
            
        } catch (Exception $e) {
            error_log('B2 yetkilendirme hatası: ' . $e->getMessage());
            throw $e;
        }
    }
    
    public function generateAudio($text, $voiceId = 'ErXwobaYiN019PkySvjV') {
        try {
            error_log("Generating audio with ElevenLabs - Voice ID: " . $voiceId);
            
            // API isteği için veriyi hazırla
            $data = [
                "text" => $text,
                "model_id" => "eleven_multilingual_v2",
                "voice_settings" => [
                    "stability" => 0.5,
                    "similarity_boost" => 0.75
                ]
            ];

            // cURL isteğini hazırla
            $ch = curl_init($this->apiUrl . "/text-to-speech/" . $voiceId);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => [
                    'Accept: audio/mpeg',
                    'Content-Type: application/json',
                    'xi-api-key: ' . $this->apiKey
                ]
            ]);

            // API'ye istek gönder
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                error_log('ElevenLabs Curl Error: ' . curl_error($ch));
                throw new Exception('API bağlantı hatası');
            }
            
            curl_close($ch);

            // Hata kontrolü
            if ($httpCode !== 200) {
                $error = json_decode($response, true);
                error_log('ElevenLabs API Error Response: ' . print_r($error, true));
                throw new Exception('Ses oluşturma başarısız oldu');
            }

            // Ses verisi kontrolü
            if (empty($response) || strlen($response) < 1000) { // Minimum ses dosyası boyutu kontrolü
                error_log('ElevenLabs API returned empty or too small audio data: ' . strlen($response) . ' bytes');
                throw new Exception('Geçersiz ses verisi');
            }

            error_log('ElevenLabs API Success - Audio size: ' . strlen($response) . ' bytes');
            
            // Dosya adı oluştur - audio_ prefix'ini kaldıralım
            $fileName = 'story_' . uniqid() . '.mp3';
            $sha1 = sha1($response);
            
            // B2'ye yükle
            $ch = curl_init($this->b2UploadUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $response,
                CURLOPT_HTTPHEADER => [
                    'Authorization: ' . $this->b2UploadAuthToken,
                    'X-Bz-File-Name: ' . rawurlencode('audio/' . $fileName),
                    'Content-Type: audio/mpeg',
                    'Content-Length: ' . strlen($response),
                    'X-Bz-Content-Sha1: ' . sha1($response)
                ],
                CURLOPT_VERBOSE => true,
                CURLOPT_HEADER => true
            ]);

            $uploadResponse = curl_exec($ch);

            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $headers = substr($uploadResponse, 0, $headerSize);
            $body = substr($uploadResponse, $headerSize);

            error_log('B2 Upload Headers: ' . $headers);
            error_log('B2 Upload Response: ' . $body);

            if (curl_errno($ch)) {
                error_log('B2 Upload Curl Error: ' . curl_error($ch));
                throw new Exception('B2 upload isteği başarısız: ' . curl_error($ch));
            }

            $result = json_decode($body, true);

            if (!isset($result['fileId'])) {
                error_log('B2 Upload Error - Raw Response: ' . $body);
                throw new Exception('B2 upload başarısız: Geçersiz yanıt');
            }

            // CDN URL'ini oluştur
            $cdnUrl = B2_CDN_URL . '/audio/' . $fileName;
            error_log('Generated CDN URL: ' . $cdnUrl);

            return $cdnUrl;
            
        } catch (Exception $e) {
            error_log('TTS Error: ' . $e->getMessage());
            throw $e;
        }
    }
}