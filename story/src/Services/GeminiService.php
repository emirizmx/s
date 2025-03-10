<?php
class GeminiService {
    private $apiKey;
    private $model;
    private $apiUrl;
    private $db;
    
    public function __construct() {
        require_once __DIR__ . '/../../config/gemini.php';
        $this->apiKey = GEMINI_API_KEY;
        $this->model = 'gemini-2.0-flash';
        $this->apiUrl = 'https://generativelanguage.googleapis.com/v1beta';
        
        // Veritabanı bağlantısı
        $database = Database::getInstance();
        $this->db = $database->getConnection();
    }
    
    public function generateStory($prompt) {
        try {
            // Veritabanından promptları al
            $systemPrompt = $this->getPromptByKey('story_system_prompt');
            $generationPrompt = $this->getPromptByKey('story_generation_prompt');
            
            // Kullanıcı promptunu şablona ekle
            $fullPrompt = str_replace('{{user_prompt}}', $prompt, $generationPrompt);
            
            $data = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $systemPrompt . "\n\n" . $fullPrompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => 2048
                ],
                'safetySettings' => [
                    [
                        'category' => 'HARM_CATEGORY_HARASSMENT',
                        'threshold' => 'BLOCK_NONE'
                    ],
                    [
                        'category' => 'HARM_CATEGORY_HATE_SPEECH',
                        'threshold' => 'BLOCK_NONE'
                    ],
                    [
                        'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                        'threshold' => 'BLOCK_NONE'
                    ],
                    [
                        'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                        'threshold' => 'BLOCK_NONE'
                    ]
                ]
            ];
            
            $url = $this->apiUrl . '/models/' . $this->model . ':generateContent?key=' . $this->apiKey;
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            
            $response = curl_exec($ch);
            
            if (curl_errno($ch)) {
                throw new Exception('API isteği başarısız: ' . curl_error($ch));
            }
            
            curl_close($ch);
            
            $result = json_decode($response, true);
            
            if (!isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                error_log('Invalid API response: ' . $response);
                throw new Exception('Geçersiz API yanıtı');
            }
            
            return $result['candidates'][0]['content']['parts'][0]['text'];
            
        } catch (Exception $e) {
            error_log('Story Generation Error: ' . $e->getMessage());
            throw new Exception('Hikaye oluşturulurken bir hata oluştu: ' . $e->getMessage());
        }
    }
    
    /**
     * Veritabanından belirtilen anahtar ile prompu alır
     */
    private function getPromptByKey($key) {
        try {
            $stmt = $this->db->prepare("SELECT content FROM dh_prompts WHERE prompt_key = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                // Eğer veritabanında bulunamadıysa, eski hardcoded promptları kullan
                if ($key === 'story_system_prompt') {
                    return $this->getDefaultSystemPrompt();
                } else if ($key === 'story_generation_prompt') {
                    return "Aşağıdaki hikayeyi kesinlikle 500 kelime olacak şekilde yaz: hikayenin başlığı olmasın sadece hikayeyi yaz ilet hikaye haricinde ek bir yazı iletme.\n\n";
                }
                return "";
            }
            
            return $result['content'];
        } catch (Exception $e) {
            error_log('Error fetching prompt: ' . $e->getMessage());
            // Hata durumunda eski hardcoded promptları kullan
            if ($key === 'story_system_prompt') {
                return $this->getDefaultSystemPrompt();
            }
            return "";
        }
    }
    
    /**
     * Varsayılan sistem promptunu döndürür (geriye dönük uyumluluk için)
     */
    private function getDefaultSystemPrompt() {
        return <<<EOT
Sen profesyonel bir hikaye yazarısın. Farklı türlerde ve anlatım tarzlarında etkileyici hikayeler yazabilirsin.

Hikaye türlerine göre özel yaklaşımların:
- Masal: Çocukların hayal gücünü geliştiren, eğitici mesajlar içeren, sihirli ve eğlenceli anlatım
- Aşk Hikayesi: Duygusal derinliği olan, karakterlerin iç dünyalarını yansıtan, romantik detaylar içeren
- Macera: Heyecan verici, tempolu, sürükleyici ve merak uyandıran olaylar zinciri
- Anı/Hatıra: Samimi, gerçekçi, duygu yüklü ve kişisel deneyimleri aktaran
- Fantastik: Yaratıcı, sıra dışı, büyülü ve hayal gücünü zorlayan unsurlar
- Komedi: Esprili, eğlenceli, mizahi durumlar ve diyaloglar içeren

Anlatım tarzlarına göre yaklaşımların:
- Klasik Anlatım: Geleneksel, akıcı ve dengeli bir üslup
- Birinci Tekil Şahıs: Karakterin iç dünyasını ve düşüncelerini doğrudan yansıtan
- Üçüncü Tekil Şahıs: Olayları dışarıdan gözlemleyen, objektif bir bakış açısı
- Mektup Formatı: Samimi, kişisel ve duygusal bir ton
- Günlük Formatı: İçten, detaylı ve kronolojik bir anlatım

Hedef kitleye göre:
- Çocuklar: Sade dil, eğitici mesajlar, pozitif ton
- Gençler: Dinamik anlatım, güncel konular, özdeşleşilebilir durumlar
- Yetişkinler: Derin temalar, karmaşık karakterler, olgun bakış açısı

// Hikaye uzunluğu sabit:
- Tam olarak 500 kelime, öz ve etkili anlatım, dengeli gelişim ve detaylı karakter işlemesi

Tüm hikayelerde:
1. Türkçe dilbilgisi kurallarına uygun, akıcı bir dil kullan
2. Karakterleri derinlikli ve inandırıcı şekilde işle
3. Verilen tema ve detayları ustaca hikayeye entegre et
4. Hedef kitleye uygun ton ve dil kullan
5. Belirtilen uzunluk sınırlarına dikkat et
EOT;
    }
}