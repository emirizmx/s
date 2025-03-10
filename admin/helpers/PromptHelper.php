<?php
class PromptHelper {
    /**
     * Veritabanında dh_prompts tablosunun varlığını kontrol eder
     * ve yoksa oluşturur
     */
    public static function ensurePromptsTableExists($db) {
        try {
            // Tablo var mı kontrolü
            $tables = $db->query("SHOW TABLES LIKE 'dh_prompts'")->fetchAll();
            
            if (count($tables) === 0) {
                // Tablo yoksa oluştur
                $sql = "CREATE TABLE IF NOT EXISTS `dh_prompts` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `name` varchar(100) NOT NULL,
                    `prompt_key` varchar(50) NOT NULL UNIQUE,
                    `content` text NOT NULL,
                    `description` text,
                    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
                
                $db->exec($sql);
                
                // Varsayılan promptları ekle
                self::insertDefaultPrompts($db);
                
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log('Error ensuring prompts table: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Varsayılan promptları veritabanına ekler
     */
    private static function insertDefaultPrompts($db) {
        $systemPrompt = 'Sen profesyonel bir hikaye yazarısın. Farklı türlerde ve anlatım tarzlarında etkileyici hikayeler yazabilirsin.

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
5. Belirtilen uzunluk sınırlarına dikkat et';

        $generationPrompt = 'Aşağıdaki hikayeyi kesinlikle 500 kelime olacak şekilde yaz: hikayenin başlığı olmasın sadece hikayeyi yaz ilet hikaye haricinde ek bir yazı iletme.

{{user_prompt}}';
        
        try {
            $stmt = $db->prepare("
                INSERT INTO dh_prompts (name, prompt_key, content, description) 
                VALUES 
                (?, ?, ?, ?),
                (?, ?, ?, ?)
            ");
            
            $stmt->execute([
                'Hikaye Sistem Promptu', 
                'story_system_prompt', 
                $systemPrompt, 
                'Hikaye üretimi için kullanılan sistem promptu',
                
                'Hikaye Oluşturma Promptu',
                'story_generation_prompt',
                $generationPrompt,
                'Kullanıcı girdisi ile hikaye oluşturmak için kullanılan prompt şablonu'
            ]);
            
            return true;
        } catch (PDOException $e) {
            error_log('Error inserting default prompts: ' . $e->getMessage());
            return false;
        }
    }
} 