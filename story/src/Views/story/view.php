<?php require_once __DIR__ . '/../../../../includes/header.php'; ?>

<!-- Header'a Plyr CSS ve JS ekleyelim -->
<link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css" />
<script src="https://cdn.plyr.io/3.7.8/plyr.polyfilled.js"></script>

<div class="container" style="margin-top: var(--header-height); padding-top: 2rem;">
    <?php if (isset($story)): ?>
        <div class="story-view" style="
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
            background: var(--surface-color);
            border-radius: 15px;
            box-shadow: var(--card-shadow);
        ">
            <h1 style="
                text-align: center;
                color: var(--heading-color);
                margin-bottom: 2rem;
                font-size: 2rem;
            "><?php echo htmlspecialchars($story['title']); ?></h1>

            <div class="story-meta" style="
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 2rem;
                padding-bottom: 1rem;
                border-bottom: 1px solid var(--border-color);
                color: var(--text-light);
                font-size: 0.9rem;
            ">
                <div>Oluşturulma: <?php echo date('d.m.Y H:i', strtotime($story['created_at'])); ?></div>
                <div>Karakterler:
                <?php 
                if (!empty($story['characters'])) {
                    $characters = is_string($story['characters']) ? json_decode($story['characters'], true) : $story['characters'];
                    if (is_array($characters)) {
                        $characterStrings = array_map(function($char) {
                            return htmlspecialchars($char['name']) . ' (' . htmlspecialchars($char['type']) . ')';
                        }, $characters);
                        echo implode(', ', $characterStrings);
                    }
                }
                ?>
                </div>
            </div>

            <?php if ($story['audio_path']): ?>
            <!-- Sesli Anlatım Player -->
            <div class="audio-player-container" style="
                background: var(--surface-color);
                border-radius: 12px;
                padding: 1.5rem;
                margin-bottom: 2rem;
                box-shadow: var(--card-shadow);
            ">
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                    <div style="color: var(--success-color); display: flex; align-items: center; gap: 0.5rem;">
                        <i class="fas fa-headphones"></i>
                        <span>Sesli Anlatım</span>
                    </div>
                    
                    <!-- İndirme Butonu -->
                    <a href="<?php echo $story['audio_path']; ?>" 
                       download="<?php echo htmlspecialchars($story['title']); ?>.mp3"
                       class="btn btn-sm btn-outline-primary"
                       style="margin-left: auto; display: flex; align-items: center; gap: 0.5rem;"
                    >
                        <i class="fas fa-download"></i>
                        <span>İndir</span>
                    </a>
                </div>

                <!-- Plyr Audio Player -->
                <audio id="player" class="audio-player">
                    <source src="<?php echo $story['audio_path']; ?>" type="audio/mp3">
                </audio>
            </div>
            <?php endif; ?>

            <div class="story-content" style="
                line-height: 1.8;
                color: var(--text-color);
                font-size: 1.1rem;
                margin-bottom: 2rem;
                white-space: pre-line;
            ">
                <?php echo nl2br(htmlspecialchars($story['content'])); ?>
            </div>

            <?php if (!$story['audio_path']): ?>
                <div class="audio-options" style="margin-bottom: 1rem;">
                    <h3 style="margin-bottom: 1rem; color: var(--heading-color);">Seslendirici Seçin</h3>
                    
                    <div class="voices-grid" style="
                        display: grid;
                        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                        gap: 1rem;
                        margin-bottom: 1.5rem;
                    ">
                        <!-- Adam -->
                        <div class="voice-card" style="
                            background: var(--surface-color);
                            border: 1px solid var(--border-color);
                            border-radius: 10px;
                            padding: 1rem;
                            cursor: pointer;
                        " data-voice-id="ErXwobaYiN019PkySvjV">
                            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.5rem;">
                                <input type="radio" name="voice" id="voice-adam" value="ErXwobaYiN019PkySvjV" checked>
                                <div>
                                    <label for="voice-adam" style="font-weight: 500; color: var(--heading-color);">Adam</label>
                                    <p style="font-size: 0.9rem; color: var(--text-light);">Derin ve güvenilir erkek sesi</p>
                                </div>
                            </div>
                            <audio controls style="width: 100%; height: 40px;">
                                <source src="<?php echo B2_CDN_URL; ?>/samples/adam.mp3" type="audio/mpeg">
                            </audio>
                        </div>

                        <!-- Rachel -->
                        <div class="voice-card" style="
                            background: var(--surface-color);
                            border: 1px solid var(--border-color);
                            border-radius: 10px;
                            padding: 1rem;
                            cursor: pointer;
                        " data-voice-id="21m00Tcm4TlvDq8ikWAM">
                            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.5rem;">
                                <input type="radio" name="voice" id="voice-rachel" value="21m00Tcm4TlvDq8ikWAM">
                                <div>
                                    <label for="voice-rachel" style="font-weight: 500; color: var(--heading-color);">Rachel</label>
                                    <p style="font-size: 0.9rem; color: var(--text-light);">Sıcak ve samimi kadın sesi</p>
                                </div>
                            </div>
                            <audio controls style="width: 100%; height: 40px;">
                                <source src="<?php echo B2_CDN_URL; ?>/samples/rachel.mp3" type="audio/mpeg">
                            </audio>
                        </div>

                        <!-- Antoni -->
                        <div class="voice-card" style="
                            background: var(--surface-color);
                            border: 1px solid var(--border-color);
                            border-radius: 10px;
                            padding: 1rem;
                            cursor: pointer;
                        " data-voice-id="pNInz6obpgDQGcFmaJgB">
                            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.5rem;">
                                <input type="radio" name="voice" id="voice-antoni" value="pNInz6obpgDQGcFmaJgB">
                                <div>
                                    <label for="voice-antoni" style="font-weight: 500; color: var(--heading-color);">Antoni</label>
                                    <p style="font-size: 0.9rem; color: var(--text-light);">Enerjik ve genç erkek sesi</p>
                                </div>
                            </div>
                            <audio controls style="width: 100%; height: 40px;">
                                <source src="<?php echo B2_CDN_URL; ?>/samples/antoni.mp3" type="audio/mpeg">
                            </audio>
                        </div>

                        <!-- Bella -->
                        <div class="voice-card" style="
                            background: var(--surface-color);
                            border: 1px solid var(--border-color);
                            border-radius: 10px;
                            padding: 1rem;
                            cursor: pointer;
                        " data-voice-id="EXAVITQu4vr4xnSDxMaL">
                            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.5rem;">
                                <input type="radio" name="voice" id="voice-bella" value="EXAVITQu4vr4xnSDxMaL">
                                <div>
                                    <label for="voice-bella" style="font-weight: 500; color: var(--heading-color);">Bella</label>
                                    <p style="font-size: 0.9rem; color: var(--text-light);">Yumuşak ve melodili kadın sesi</p>
                                </div>
                            </div>
                            <audio controls style="width: 100%; height: 40px;">
                                <source src="<?php echo B2_CDN_URL; ?>/samples/bella.mp3" type="audio/mpeg">
                            </audio>
                        </div>
                    </div>

                    <button id="generateAudio" 
                            class="btn-audio" 
                            style="
                                width: 100%;
                                padding: 1rem;
                                background: var(--primary-color);
                                color: white;
                                border: none;
                                border-radius: 10px;
                                font-size: 1.1rem;
                                cursor: pointer;
                                margin-bottom: 1rem;
                            "
                            data-story-id="<?php echo $story['id']; ?>"
                    >
                        Sesli Anlatım Oluştur (50 Kredi)
                    </button>
                </div>

                <script>
                // Kart tıklaması ile radio button seçimi
                document.querySelectorAll('.voice-card').forEach(card => {
                    card.addEventListener('click', function() {
                        const radio = this.querySelector('input[type="radio"]');
                        radio.checked = true;
                    });
                });

                // Ses oluşturma
                document.getElementById('generateAudio')?.addEventListener('click', async function() {
                    const storyId = this.dataset.storyId;
                    const voiceId = document.querySelector('input[name="voice"]:checked').value;
                    const button = this;
                    
                    try {
                        button.disabled = true;
                        button.textContent = 'Ses Dosyası Oluşturuluyor...';
                        
                        const response = await fetch(`index.php?action=generateAudio&id=${storyId}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ voiceId: voiceId })
                        });
                        
                        if (!response.ok) {
                            throw new Error('Sunucu hatası');
                        }
                        
                        const data = await response.json();
                        
                        if (data.error) {
                            throw new Error(data.error);
                        }
                        
                        window.location.href = `index.php?action=view&id=${storyId}`;
                        
                    } catch (error) {
                        alert(error.message);
                        button.disabled = false;
                        button.textContent = 'Sesli Anlatım Oluştur (50 Kredi)';
                    }
                });
                </script>
            <?php else: ?>
                <div class="audio-player" style="
                    margin-top: 2rem;
                    padding: 1rem;
                    background: var(--info-bg);
                    border-radius: 10px;
                ">
                    <h3 style="margin-bottom: 1rem; color: var(--heading-color);">Sesli Anlatım</h3>
                    <audio controls style="width: 100%;">
                        <source src="<?php echo htmlspecialchars($story['audio_path']); ?>" type="audio/mpeg">
                        Tarayıcınız audio elementini desteklemiyor.
                    </audio>
                </div>
            <?php endif; ?>

            <div class="share-controls" style="
                margin-top: 2rem;
                padding: 1rem;
                background: var(--surface-color);
                border-radius: 10px;
                border: 1px solid var(--border-color);
            ">
                <h3 style="margin-bottom: 1rem; color: var(--heading-color);">Paylaşım Ayarları</h3>
                
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                    <label class="switch">
                        <input type="checkbox" id="isPublic" <?php echo $story['is_public'] ? 'checked' : ''; ?>>
                        <span class="slider round"></span>
                    </label>
                    <span>Herkese Açık</span>
                </div>
                
                <?php if ($story['share_token']): ?>
                <div id="shareUrlContainer" style="
                    display: <?php echo $story['is_public'] ? 'block' : 'none'; ?>;
                    margin-top: 1rem;
                ">
                    <label style="display: block; margin-bottom: 0.5rem;">Paylaşım Bağlantısı:</label>
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="text" 
                               value="https://dijitalhediye.com/story/share/<?php echo $story['share_token']; ?>"
                               readonly
                               style="
                                   flex: 1;
                                   padding: 0.5rem;
                                   border: 1px solid var(--border-color);
                                   border-radius: 5px;
                                   background: var(--background-color);
                               ">
                        <button onclick="copyShareUrl(this)" style="
                            padding: 0.5rem 1rem;
                            background: var(--primary-color);
                            color: white;
                            border: none;
                            border-radius: 5px;
                            cursor: pointer;
                        ">Kopyala</button>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <script>
            document.getElementById('isPublic')?.addEventListener('change', async function() {
                const isPublic = this.checked;
                const storyId = <?php echo $story['id']; ?>;
                
                try {
                    const response = await fetch(`index.php?action=share&id=${storyId}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ 
                            is_public: isPublic 
                        })
                    });
                    
                    if (!response.ok) {
                        throw new Error('Sunucu hatası');
                    }
                    
                    const result = await response.json();
                    
                    if (result.error) {
                        throw new Error(result.error);
                    }
                    
                    const shareUrlContainer = document.getElementById('shareUrlContainer');
                    if (isPublic) {
                        location.reload(); // Yeni share_token için sayfayı yenile
                    } else {
                        shareUrlContainer.style.display = 'none';
                    }
                    
                } catch (error) {
                    alert(error.message);
                    this.checked = !isPublic; // Hata durumunda switch'i eski haline getir
                }
            });

            function copyShareUrl(button) {
                const input = button.parentElement.querySelector('input');
                input.select();
                document.execCommand('copy');
                
                const originalText = button.textContent;
                button.textContent = 'Kopyalandı!';
                setTimeout(() => button.textContent = originalText, 2000);
            }
            </script>

            <div class="story-actions" style="
                margin-top: 2rem;
                display: flex;
                gap: 1rem;
                justify-content: center;
            ">
                <a href="/story" 
                   class="btn-back" 
                   style="
                       padding: 0.8rem 1.5rem;
                       background: var(--surface-color);
                       border: 1px solid var(--border-color);
                       border-radius: 10px;
                       color: var(--text-color);
                       text-decoration: none;
                   "
                >
                    Geri Dön
                </a>
                
                <a href="/story/list" 
                   class="btn-list" 
                   style="
                       padding: 0.8rem 1.5rem;
                       background: var(--surface-color);
                       border: 1px solid var(--border-color);
                       border-radius: 10px;
                       color: var(--text-color);
                       text-decoration: none;
                   "
                >
                    Masallarım
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="error-message" style="text-align: center; color: var(--error-color);">
            Masal bulunamadı.
        </div>
    <?php endif; ?>
</div>

<!-- Player Ayarları -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.audio-player')) {
        const player = new Plyr('#player', {
            controls: [
                'play',
                'progress',
                'current-time',
                'duration',
                'mute',
                'volume',
                'speed'
            ],
            speed: {
                selected: 1,
                options: [0.75, 1, 1.25, 1.5]
            },
            keyboard: {
                focused: true,
                global: true
            },
            tooltips: {
                controls: true,
                seek: true
            },
            i18n: {
                speed: 'Hız',
                normal: 'Normal',
                play: 'Oynat',
                pause: 'Duraklat',
                mute: 'Sessiz',
                unmute: 'Sesi Aç',
                download: 'İndir',
                currentTime: 'Geçen Süre',
                duration: 'Toplam Süre'
            }
        });

        // Player'ı özelleştir
        const playerElement = document.querySelector('.plyr');
        if (playerElement) {
            playerElement.style.cssText = `
                --plyr-color-main: var(--primary-color);
                --plyr-range-fill-background: var(--primary-color);
                --plyr-audio-controls-background: transparent;
                --plyr-audio-control-color: var(--text-color);
                --plyr-audio-control-color-hover: var(--primary-color);
                border-radius: 8px;
                overflow: hidden;
            `;
        }
    }
});
</script>

<style>
/* Player özel stilleri */
.audio-player-container .plyr {
    background: var(--surface-color);
}

.audio-player-container .plyr__controls {
    padding: 0.5rem;
    border-radius: 8px;
    background: var(--surface-hover);
}

.audio-player-container .plyr__control {
    transition: all 0.2s ease;
}

.audio-player-container .plyr__control:hover {
    background: var(--primary-light);
}

/* İndirme butonu hover efekti */
.audio-player-container .btn-outline-primary:hover {
    background: var(--primary-light);
    color: var(--primary-color);
}
</style>

<?php require_once __DIR__ . '/../../../../includes/footer.php'; ?>